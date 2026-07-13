<?php

declare(strict_types=1);

class DocumentSyncController
{
    public function __construct(private AuthService $authService, private DocumentImportService $documentImportService)
    {
    }

    public function index(): string
    {
        $this->authService->requireRole('admin');

        return view('admin.document-sync', [
            'title' => 'Sincronització de documents',
            'result' => null,
            'error' => null,
            'jsonPayload' => '',
        ]);
    }

    public function store(): string
    {
        $this->authService->requireRole('admin');

        $jsonPayload = $this->extractJsonPayload();
        $result = null;
        $error = null;

        try {
            $payload = json_decode($jsonPayload, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($payload)) {
                throw new RuntimeException('El JSON no té una estructura vàlida.');
            }

            $result = $this->documentImportService->importPayload($payload);
        } catch (Throwable $throwable) {
            $error = $throwable->getMessage();
        }

        return view('admin.document-sync', [
            'title' => 'Sincronització de documents',
            'result' => $result,
            'error' => $error,
            'jsonPayload' => $jsonPayload,
        ]);
    }

    private function extractJsonPayload(): string
    {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? ''));

        if (str_contains($contentType, 'application/json')) {
            $rawInput = file_get_contents('php://input');
            if (is_string($rawInput) && trim($rawInput) !== '') {
                return $rawInput;
            }
        }

        return (string) ($_POST['json_payload'] ?? '');
    }
}
