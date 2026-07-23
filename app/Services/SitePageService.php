<?php

declare(strict_types=1);

use Google\Client;
use Google\Service\Docs;
use Google\Service\Docs\Document;
use Google\Service\Docs\StructuralElement;
use GuzzleHttp\Client as HttpClient;

class SitePageService
{
    private ?PDO $connection = null;

    public function aboutContent(): ?array
    {
        $documentId = $this->setting('public_about_google_doc_id');
        if ($documentId === null || $documentId === '') {
            return null;
        }

        return $this->googleDocumentContent($documentId);
    }

    private function setting(string $key): ?string
    {
        $stmt = $this->pdo()->prepare('SELECT `value` FROM settings WHERE `key` = :key LIMIT 1');
        $stmt->execute(['key' => $key]);
        $value = $stmt->fetchColumn();

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function googleDocumentContent(string $documentId): ?array
    {
        $config = require dirname(__DIR__, 2) . '/config/google.php';
        if (($config['enabled'] ?? false) !== true) {
            return null;
        }

        $serviceAccountPath = $this->absolutePath((string) ($config['service_account_path'] ?? ''));
        if ($serviceAccountPath === '' || !is_file($serviceAccountPath)) {
            return null;
        }

        try {
            $client = new Client();
            $client->setAuthConfig($serviceAccountPath);
            $client->setScopes($config['scopes'] ?? []);

            $caBundlePath = $this->absolutePath((string) ($config['ca_bundle_path'] ?? ''));
            if ($caBundlePath !== '' && is_file($caBundlePath)) {
                $client->setHttpClient(new HttpClient(['verify' => $caBundlePath]));
            }

            $document = (new Docs($client))->documents->get($documentId);
            $blocks = $this->extractBlocks($document);

            return [
                'title' => $document->getTitle() ?: 'Què és Entorns de Natura',
                'blocks' => $blocks,
            ];
        } catch (Throwable) {
            return null;
        }
    }

    private function absolutePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (preg_match('#^[a-zA-Z]:[\\/]#', $path) === 1 || str_starts_with($path, '/')) {
            return $path;
        }

        return dirname(__DIR__, 2) . '/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    private function extractBlocks(Document $document): array
    {
        $body = $document->getBody();
        if ($body === null) {
            return [];
        }

        $blocks = [];
        $listCounters = [];
        $content = $body->getContent() ?? [];
        $listIndentBaselines = $this->listIndentBaselines($content);

        foreach ($content as $structuralElement) {
            if (!$structuralElement instanceof StructuralElement) {
                continue;
            }

            $segments = $this->extractStructuralElementSegments($structuralElement);
            $rawText = $this->segmentsText($segments);
            $typedIndentLevel = $this->typedIndentLevel($rawText);
            if ($typedIndentLevel > 0) {
                $segments = $this->removeLeadingIndentFromSegments($segments);
            }

            $text = $this->cleanGoogleText($this->segmentsText($segments));
            if ($text !== '') {
                $blocks[] = $this->blockFromStructuralElement($document, $structuralElement, $text, $segments, $typedIndentLevel, $listCounters, $listIndentBaselines);
            }
        }

        return $blocks;
    }

    private function blockFromStructuralElement(Document $document, StructuralElement $structuralElement, string $text, array $segments, int $typedIndentLevel, array &$listCounters, array $listIndentBaselines): array
    {
        $paragraph = $structuralElement->getParagraph();
        $textStyle = $this->textStyleType($structuralElement);

        if ($paragraph?->getBullet() !== null) {
            $bullet = $paragraph->getBullet();
            $listId = (string) ($bullet->getListId() ?? '');
            $nestingLevel = (int) ($bullet->getNestingLevel() ?? 0);
            $indentLevel = max($typedIndentLevel, $this->listIndentLevel($structuralElement, $nestingLevel, $listIndentBaselines[$listId] ?? null));
            $listKind = $this->listKind($document, $structuralElement);
            $ordinal = null;

            if ($listKind === 'ordered') {
                $counterKey = $listId . ':' . $indentLevel;
                $listCounters[$counterKey] = (int) ($listCounters[$counterKey] ?? 0) + 1;
                $ordinal = $listCounters[$counterKey];
            }

            return [
                'type' => 'list_item',
                'list_kind' => $listKind,
                'list_id' => $listId,
                'nesting_level' => $nestingLevel,
                'indent_level' => $indentLevel,
                'ordinal' => $ordinal,
                'text_style' => $textStyle,
                'text' => $text,
                'segments' => $segments,
            ];
        }

        return [
            'type' => $textStyle,
            'text' => $text,
            'segments' => $segments,
        ];
    }

    private function blockType(StructuralElement $structuralElement): string
    {
        return $this->textStyleType($structuralElement);
    }

    private function textStyleType(StructuralElement $structuralElement): string
    {
        $paragraph = $structuralElement->getParagraph();
        $style = $paragraph?->getParagraphStyle();
        $namedStyleType = (string) ($style?->getNamedStyleType() ?? '');

        return match ($namedStyleType) {
            'TITLE' => 'title',
            'HEADING_1' => 'heading_1',
            'HEADING_2' => 'heading_2',
            'HEADING_3' => 'heading_3',
            'HEADING_4' => 'heading_4',
            default => 'paragraph',
        };
    }

    private function listKind(Document $document, StructuralElement $structuralElement): string
    {
        $bullet = $structuralElement->getParagraph()?->getBullet();
        $listId = (string) ($bullet?->getListId() ?? '');
        $nestingLevel = (int) ($bullet?->getNestingLevel() ?? 0);
        $lists = $document->getLists() ?? [];
        $list = is_array($lists) ? ($lists[$listId] ?? null) : null;
        $levels = $list?->getListProperties()?->getNestingLevels() ?? [];
        $level = is_array($levels) ? ($levels[$nestingLevel] ?? null) : null;
        $glyphType = strtoupper((string) ($level?->getGlyphType() ?? ''));

        if (in_array($glyphType, ['DECIMAL', 'ZERO_DECIMAL', 'UPPER_ALPHA', 'ALPHA', 'LOWER_ALPHA', 'UPPER_ROMAN', 'ROMAN', 'LOWER_ROMAN'], true)) {
            return 'ordered';
        }

        return 'unordered';
    }

    private function listIndentBaselines(array $content): array
    {
        $baselines = [];

        foreach ($content as $structuralElement) {
            if (!$structuralElement instanceof StructuralElement) {
                continue;
            }

            $paragraph = $structuralElement->getParagraph();
            $bullet = $paragraph?->getBullet();
            $listId = (string) ($bullet?->getListId() ?? '');
            $magnitude = $paragraph?->getParagraphStyle()?->getIndentStart()?->getMagnitude();

            if ($listId === '' || !is_numeric($magnitude)) {
                continue;
            }

            $magnitude = (float) $magnitude;
            $baselines[$listId] = isset($baselines[$listId]) ? min($baselines[$listId], $magnitude) : $magnitude;
        }

        return $baselines;
    }

    private function listIndentLevel(StructuralElement $structuralElement, int $fallbackLevel, ?float $baseline): int
    {
        $paragraphStyle = $structuralElement->getParagraph()?->getParagraphStyle();
        $indentStart = $paragraphStyle?->getIndentStart();
        $magnitude = $indentStart?->getMagnitude();

        if (!is_numeric($magnitude)) {
            return $fallbackLevel;
        }

        $base = $baseline ?? (float) $magnitude;
        $indentLevel = (int) round(max(0.0, ((float) $magnitude - $base) / 36.0));

        return max($fallbackLevel, $indentLevel);
    }

    private function cleanGoogleText(string $text, bool $trim = true): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/\x1B\[[0-9;?]*[ -\/]*[@-~]/', '', $text) ?? $text;
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;
        $text = preg_replace("/[ \t]+\n/u", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;

        return $trim ? trim($text) : $text;
    }

    private function segmentsText(array $segments): string
    {
        $text = '';
        foreach ($segments as $segment) {
            $text .= (string) ($segment['text'] ?? '');
        }

        return $text;
    }

    private function typedIndentLevel(string $text): int
    {
        if (preg_match('/^(\t+| {4,})/u', $text, $matches) !== 1) {
            return 0;
        }

        $indent = $matches[1];
        $tabs = substr_count($indent, "\t");
        $spaces = intdiv(strlen(str_replace("\t", '', $indent)), 4);

        return max(0, $tabs + $spaces);
    }

    private function removeLeadingIndentFromSegments(array $segments): array
    {
        foreach ($segments as $index => $segment) {
            $text = (string) ($segment['text'] ?? '');
            if ($text === '') {
                continue;
            }

            $segments[$index]['text'] = preg_replace('/^(\t+| {4,})/u', '', $text) ?? $text;
            break;
        }

        return $segments;
    }

    private function extractStructuralElementSegments(StructuralElement $structuralElement): array
    {
        $paragraph = $structuralElement->getParagraph();
        if ($paragraph === null) {
            return [];
        }

        $segments = [];
        foreach ($paragraph->getElements() ?? [] as $paragraphElement) {
            $textRun = $paragraphElement->getTextRun();
            if ($textRun === null) {
                continue;
            }

            $text = $this->cleanGoogleText((string) $textRun->getContent(), false);
            if (trim($text) === '') {
                continue;
            }

            $style = $textRun->getTextStyle();
            $segments[] = [
                'text' => $text,
                'bold' => $style?->getBold() === true,
            ];
        }

        return $segments;
    }

    private function extractStructuralElementText(StructuralElement $structuralElement): string
    {
        $paragraph = $structuralElement->getParagraph();
        if ($paragraph === null) {
            return '';
        }

        $text = '';
        foreach ($paragraph->getElements() ?? [] as $paragraphElement) {
            $textRun = $paragraphElement->getTextRun();
            if ($textRun !== null) {
                $text .= (string) $textRun->getContent();
            }
        }

        return $text;
    }

    private function pdo(): PDO
    {
        if ($this->connection === null) {
            $this->connection = require dirname(__DIR__, 2) . '/config/database.php';
        }

        return $this->connection;
    }
}
