<?php

declare(strict_types=1);

class AdminDashboardService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function dashboardData(): array
    {
        $users = $this->users();
        $roles = $this->roles();
        $typicalProjectRoleNames = ['coordinador/a', 'informàtic/a', 'cartògraf/a', 'científic/a'];
        $projectRoles = $this->projectRoles($typicalProjectRoleNames);
        $projectMembersWithoutRole = $this->projectMembersWithoutRole($typicalProjectRoleNames);
        $projectTeamMembershipCount = (int) $this->pdo->query('SELECT COUNT(*) FROM project_team_members')->fetchColumn();
        $projects = $this->projects();
        $userRoles = $this->userRoles();
        $classes = $this->classes();
        $academicYears = $this->academicYears();
        $projectAcademicYears = $this->projectAcademicYears();
        $projectAssignments = $this->projectAssignments();
        $projectTeamData = $this->projectTeamData($typicalProjectRoleNames);
        $sitePages = $this->sitePages();
        $classrooms = $this->classrooms();
        $classroomSummary = $this->classroomSummary();
        $classMemberships = $this->classMemberships();
        $classTeachers = $this->classTeachers();
        $assessmentStructure = (new AdminAssessmentStructureService($this->pdo))->assessmentStructure();

        $roleMap = [];
        foreach ($userRoles as $row) {
            $roleMap[(int) $row['user_id']][] = (string) $row['role_name'];
        }

        $userClassMap = [];
        $userClassGroupMap = [];
        $userClassCodeMap = [];
        $userAcademicYearMap = [];
        foreach ($classMemberships as $membership) {
            $userClassMap[(int) $membership['user_id']] = (int) $membership['class_id'];
            $userClassGroupMap[(int) $membership['user_id']] = (string) $membership['class_name'];
            $userClassCodeMap[(int) $membership['user_id']] = (string) $membership['class_code'];
            $userAcademicYearMap[(int) $membership['user_id']] = [
                'id' => (int) $membership['academic_year_id'],
                'name' => (string) $membership['academic_year_name'],
            ];
        }

        foreach ($users as &$user) {
            $user['roles'] = $roleMap[(int) $user['id']] ?? [];
            $user['status'] = ((int) $user['is_active'] === 1) ? 'Actiu' : 'Inactiu';
            $user['class_id'] = $userClassMap[(int) $user['id']] ?? null;
            $user['class_group'] = $userClassGroupMap[(int) $user['id']] ?? null;
            $user['class_code'] = $userClassCodeMap[(int) $user['id']] ?? null;
            $user['academic_year'] = $userAcademicYearMap[(int) $user['id']] ?? null;
        }
        unset($user);

        $studentUsers = array_values(array_filter(
            $users,
            static fn (array $user): bool => in_array('student', $user['roles'], true)
        ));

        $teacherUsers = array_values(array_filter(
            $users,
            static fn (array $user): bool => in_array('teacher', $user['roles'], true)
        ));

        $classTeachersMap = [];
        $teacherClassMap = [];
        foreach ($classTeachers as $teacherAssignment) {
            $classId = (int) $teacherAssignment['class_id'];
            $teacherId = (int) $teacherAssignment['user_id'];
            $classTeachersMap[$classId][] = [
                'id' => $teacherId,
                'name' => trim((string) $teacherAssignment['name'] . ' ' . (string) $teacherAssignment['surname']),
            ];
            $teacherClassMap[$teacherId][] = [
                'id' => $classId,
                'name' => (string) $teacherAssignment['class_name'],
                'code' => (string) $teacherAssignment['class_code'],
            ];
        }

        $analytics = (new AnalyticsService())->getDashboardStats($this->pdo);

        return [
            'users' => $users,
            'roles' => $roles,
            'projects' => $projects,
            'classes' => $classes,
            'academicYears' => $academicYears,
            'projectAcademicYears' => $projectAcademicYears,
            'studentUsers' => $studentUsers,
            'teacherUsers' => $teacherUsers,
            'classTeachersMap' => $classTeachersMap,
            'teacherClassMap' => $teacherClassMap,
            'projectAssignments' => $projectAssignments,
            'projectTeams' => array_values($projectTeamData['projectTeams']),
            'projectRoleGroups' => array_values($projectTeamData['projectRoleGroups']),
            'sitePages' => $sitePages,
            'classrooms' => $classrooms,
            'classroomSummary' => $classroomSummary,
            'projectRoles' => $projectRoles,
            'projectMembersWithoutRole' => $projectMembersWithoutRole,
            'projectTeamMembershipCount' => $projectTeamMembershipCount,
            'assessmentStructure' => $assessmentStructure,
            'roleMap' => $roleMap,
            'analytics' => $analytics,
            'geoMapPoints' => $this->buildGeoMapPoints($analytics['geo_stats'] ?? []),
        ];
    }

    private function users(): array
    {
        $stmt = $this->pdo->query(
            'SELECT u.id, u.name, u.surname, u.email, u.is_active, u.created_at,
                    u.gender, u.article, u.inaturalist_user_login,
                    COUNT(DISTINCT sv.id) AS visit_count
              FROM users u
              LEFT JOIN site_visits sv ON sv.user_id = u.id
              GROUP BY u.id, u.name, u.surname, u.email, u.is_active, u.created_at,
                       u.gender, u.article, u.inaturalist_user_login
              ORDER BY u.created_at DESC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function sitePages(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, slug, language_code, title, google_file_id, last_synced_at, last_sync_status, last_sync_error, is_active,
                    CHAR_LENGTH(COALESCE(content_json, "")) AS content_json_length
               FROM site_pages
              ORDER BY language_code, slug'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function classroomSummary(): array
    {
        $stmt = $this->pdo->query(
            'SELECT
                COUNT(DISTINCT c.id) AS total,
                COUNT(DISTINCT CASE WHEN c.is_active = 1 THEN c.id END) AS active,
                COUNT(DISTINCT CASE WHEN c.is_active = 0 THEN c.id END) AS archived,
                COUNT(DISTINCT cm.id) AS members
             FROM classrooms c
             LEFT JOIN classroom_members cm ON cm.classroom_id = c.id'
        );
        $summary = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total' => (int) ($summary['total'] ?? 0),
            'active' => (int) ($summary['active'] ?? 0),
            'archived' => (int) ($summary['archived'] ?? 0),
            'members' => (int) ($summary['members'] ?? 0),
        ];
    }

    private function classrooms(): array
    {
        $stmt = $this->pdo->query(
            'SELECT
                c.id,
                c.classroom_key,
                c.classroom_name,
                c.classroom_url,
                c.google_classroom_id,
                c.is_active,
                p.name AS project_name,
                p.slug AS project_slug,
                ay.name AS academic_year_name,
                ay.is_current AS academic_year_is_current,
                COUNT(DISTINCT cm.id) AS member_count,
                COUNT(DISTINCT atcl.id) AS task_link_count
             FROM classrooms c
             INNER JOIN project_academic_years pay ON pay.id = c.project_academic_year_id
             INNER JOIN projects p ON p.id = pay.project_id
             INNER JOIN academic_years ay ON ay.id = pay.academic_year_id
             LEFT JOIN classroom_members cm ON cm.classroom_id = c.id AND cm.is_active = 1
             LEFT JOIN assessment_task_classroom_links atcl ON atcl.classroom_id = c.id
             GROUP BY c.id, c.classroom_key, c.classroom_name, c.classroom_url, c.google_classroom_id, c.is_active,
                      p.name, p.slug, ay.name, ay.is_current
             ORDER BY ay.is_current DESC, ay.start_year DESC, p.display_order, p.name, c.is_active DESC, c.classroom_name'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function roles(): array
    {
        $stmt = $this->pdo->query(
            'SELECT r.id, r.name, COUNT(ur.user_id) AS user_count
             FROM web_roles r
             LEFT JOIN user_web_roles ur ON ur.role_id = r.id
             GROUP BY r.id, r.name
             ORDER BY r.name'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function projectRoles(array $typicalProjectRoleNames): array
    {
        $placeholders = implode(',', array_fill(0, count($typicalProjectRoleNames), '?'));
        $stmt = $this->pdo->prepare(
            'SELECT pr.id, pr.name, COUNT(DISTINCT ptmr.project_team_member_id) AS member_count
               FROM project_roles pr
               LEFT JOIN project_team_member_roles ptmr ON ptmr.project_role_id = pr.id
              WHERE pr.name IN (' . $placeholders . ')
              GROUP BY pr.id, pr.name
              ORDER BY CASE pr.name
                  WHEN "coordinador/a" THEN 1
                  WHEN "informàtic/a" THEN 2
                  WHEN "cartògraf/a" THEN 3
                  WHEN "científic/a" THEN 4
                  ELSE 99
              END, pr.name'
        );
        $stmt->execute($typicalProjectRoleNames);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function projectMembersWithoutRole(array $typicalProjectRoleNames): int
    {
        $placeholders = implode(',', array_fill(0, count($typicalProjectRoleNames), '?'));
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
               FROM project_team_members ptm
               LEFT JOIN project_team_member_roles ptmr ON ptmr.project_team_member_id = ptm.id
               LEFT JOIN project_roles pr ON pr.id = ptmr.project_role_id
                AND pr.name IN (' . $placeholders . ')
              GROUP BY ptm.id
             HAVING COUNT(pr.id) = 0'
        );
        $stmt->execute($typicalProjectRoleNames);

        return count($stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function projects(): array
    {
        $stmt = $this->pdo->query(
            'SELECT p.id, p.name, p.slug, p.display_order, p.is_active, p.created_at,
                    COALESCE(pt.description, "") AS description
              FROM projects p
             LEFT JOIN languages l ON l.code = "ca"
             LEFT JOIN project_translations pt ON pt.project_id = p.id AND pt.language_id = l.id
             ORDER BY p.display_order, p.name'
        );
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $projectIds = array_map(static fn (array $project): int => (int) $project['id'], $projects);
        $projectAssetService = new ProjectAssetService();
        $assetsByProject = $projectAssetService->assetsByProjectIds($projectIds);
        $logoAssetsByProject = $projectAssetService->logoAssetByProjectIds($projectIds);

        foreach ($projects as &$project) {
            $project['assets'] = $assetsByProject[(int) $project['id']] ?? [];
            $project['logo_asset'] = $logoAssetsByProject[(int) $project['id']] ?? null;
        }
        unset($project);

        return $projects;
    }

    private function userRoles(): array
    {
        $stmt = $this->pdo->query(
            'SELECT ur.user_id, r.name AS role_name
             FROM user_web_roles ur
             INNER JOIN web_roles r ON r.id = ur.role_id
             ORDER BY ur.user_id, r.name'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function classes(): array
    {
        $stmt = $this->pdo->query(
            'SELECT c.id, c.class_name AS name, c.class_code AS code, c.academic_year_id, ay.name AS academic_year_name
               FROM classes c
               INNER JOIN academic_years ay ON ay.id = c.academic_year_id
              ORDER BY ay.start_year ASC, c.class_code ASC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function academicYears(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, start_year, end_year, is_current FROM academic_years ORDER BY start_year ASC, end_year ASC');

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function projectAcademicYears(): array
    {
        $stmt = $this->pdo->query(
            'SELECT pay.id, pay.project_id, pay.academic_year_id, ay.name AS academic_year_name, pay.status
               FROM project_academic_years pay
               INNER JOIN academic_years ay ON ay.id = pay.academic_year_id
              ORDER BY ay.start_year ASC, pay.project_id ASC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function projectAssignments(): array
    {
        $stmt = $this->pdo->query(
            'SELECT pg.id, pg.class_id, p.id AS project_id, ay.name AS academic_year_name, pg.status, pg.created_at,
                    c.class_name AS class_name,
                    c.class_code AS class_code,
                    p.name AS project_name,
                    p.slug AS project_slug
              FROM project_class_assignments pg
              INNER JOIN classes c ON c.id = pg.class_id
              INNER JOIN project_academic_years pay ON pay.id = pg.project_academic_year_id
              INNER JOIN projects p ON p.id = pay.project_id
              INNER JOIN academic_years ay ON ay.id = pay.academic_year_id
              ORDER BY c.class_name, ay.id, p.display_order, p.name'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function projectTeamData(array $typicalProjectRoleNames): array
    {
        $stmt = $this->pdo->query(
            'SELECT pt.id AS project_team_id,
                    pt.project_academic_year_id,
                    pt.team_code,
                    pt.team_name,
                    pt.class_group,
                    pt.display_order,
                    pt.is_active,
                    p.name AS project_name,
                    p.slug AS project_slug,
                    ay.name AS academic_year_name,
                    ptm.user_id,
                    ptm.class_id AS member_class_id,
                    u.name AS member_name,
                    u.surname AS member_surname,
                    u.email AS member_email,
                    mc.class_code AS member_class_code,
                    GROUP_CONCAT(DISTINCT pr.name ORDER BY pr.name SEPARATOR "||") AS project_role_names
               FROM project_teams pt
               INNER JOIN project_academic_years pay ON pay.id = pt.project_academic_year_id
               INNER JOIN projects p ON p.id = pay.project_id
               INNER JOIN academic_years ay ON ay.id = pay.academic_year_id
               LEFT JOIN project_team_members ptm ON ptm.project_team_id = pt.id
               LEFT JOIN users u ON u.id = ptm.user_id
               LEFT JOIN classes mc ON mc.id = ptm.class_id
               LEFT JOIN project_team_member_roles ptmr ON ptmr.project_team_member_id = ptm.id
               LEFT JOIN project_roles pr ON pr.id = ptmr.project_role_id
              GROUP BY pt.id, pt.project_academic_year_id, pt.team_code, pt.team_name, pt.class_group,
                       pt.display_order, pt.is_active, p.name, p.slug, ay.name, ptm.id, ptm.user_id,
                       ptm.class_id, u.name, u.surname, u.email, mc.class_code
              ORDER BY ay.id, p.display_order, p.name, pt.display_order, pt.team_code, u.surname, u.name'
        );
        $projectTeams = [];
        $projectRoleGroups = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $projectTeamId = (int) $row['project_team_id'];
            $roleNames = array_values(array_intersect(
                $this->splitStoredProjectRoleNames((string) ($row['project_role_names'] ?? '')),
                $typicalProjectRoleNames
            ));
            if (!isset($projectTeams[$projectTeamId])) {
                $projectTeams[$projectTeamId] = [
                    'id' => $projectTeamId,
                    'project_academic_year_id' => (int) $row['project_academic_year_id'],
                    'team_code' => (string) $row['team_code'],
                    'team_name' => (string) ($row['team_name'] ?? ''),
                    'class_group' => (string) ($row['class_group'] ?? ''),
                    'display_order' => (int) $row['display_order'],
                    'is_active' => (int) $row['is_active'],
                    'project_name' => (string) $row['project_name'],
                    'project_slug' => (string) $row['project_slug'],
                    'academic_year_name' => (string) $row['academic_year_name'],
                    'members' => [],
                ];
            }

            if (!empty($row['user_id'])) {
                $member = [
                    'id' => (int) $row['user_id'],
                    'name' => trim((string) $row['member_name'] . ' ' . (string) $row['member_surname']),
                    'email' => (string) ($row['member_email'] ?? ''),
                    'class_id' => !empty($row['member_class_id']) ? (int) $row['member_class_id'] : null,
                    'class_code' => (string) ($row['member_class_code'] ?? ''),
                    'project_role_names' => $roleNames,
                    'team_code' => (string) $row['team_code'],
                    'team_name' => (string) ($row['team_name'] ?? ''),
                    'class_group' => (string) ($row['class_group'] ?? ''),
                    'project_name' => (string) $row['project_name'],
                    'academic_year_name' => (string) $row['academic_year_name'],
                    'project_slug' => (string) $row['project_slug'],
                ];

                $projectTeams[$projectTeamId]['members'][] = [
                    'id' => $member['id'],
                    'name' => $member['name'],
                    'email' => $member['email'],
                    'class_code' => $member['class_code'],
                    'project_role_names' => $member['project_role_names'],
                ];

                $roleGroupNames = $roleNames !== [] ? $roleNames : ['Sense rol'];
                foreach ($roleGroupNames as $roleGroupName) {
                    if (!isset($projectRoleGroups[$roleGroupName])) {
                        $projectRoleGroups[$roleGroupName] = [
                            'name' => $roleGroupName,
                            'members' => [],
                        ];
                    }

                    $projectRoleGroups[$roleGroupName]['members'][] = $member;
                }
            }
        }

        return [
            'projectTeams' => $projectTeams,
            'projectRoleGroups' => $projectRoleGroups,
        ];
    }

    private function classMemberships(): array
    {
        $stmt = $this->pdo->query(
            'SELECT cm.user_id, cm.class_id, c.class_name AS class_name, c.class_code,
                    c.academic_year_id, ay.name AS academic_year_name
              FROM class_members cm
              INNER JOIN classes c ON c.id = cm.class_id
              INNER JOIN academic_years ay ON ay.id = c.academic_year_id
              ORDER BY cm.user_id, ay.start_year, c.class_name'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function classTeachers(): array
    {
        $stmt = $this->pdo->query(
            'SELECT ct.class_id, ct.user_id, c.class_name AS class_name, c.class_code, u.name, u.surname
              FROM class_teachers ct
              INNER JOIN classes c ON c.id = ct.class_id
              INNER JOIN users u ON u.id = ct.user_id
              ORDER BY c.class_name, u.name, u.surname'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buildGeoMapPoints(array $geoStats): array
    {
        $points = [];
        $countryCoordinates = [
            'ES' => [40.4168, -3.7038],
            'PT' => [38.7223, -9.1393],
            'FR' => [48.8566, 2.3522],
            'GB' => [51.5072, -0.1276],
            'DE' => [52.52, 13.405],
            'IT' => [41.9028, 12.4964],
            'BE' => [50.8503, 4.3517],
            'NL' => [52.3676, 4.9041],
            'CH' => [46.948, 7.4474],
            'US' => [38.9072, -77.0369],
            'CA' => [45.4215, -75.6972],
            'MX' => [19.4326, -99.1332],
            'BR' => [-15.7939, -47.8828],
            'AR' => [-34.6037, -58.3816],
            'CL' => [-33.4489, -70.6693],
            'PE' => [-12.0464, -77.0428],
            'CO' => [4.711, -74.0721],
        ];

        foreach ($geoStats as $row) {
            $countryCode = strtoupper(trim((string) ($row['country_code'] ?? '')));
            if ($countryCode === '') {
                continue;
            }

            $coordinates = $countryCoordinates[$countryCode] ?? null;
            if ($coordinates === null) {
                continue;
            }

            $region = trim((string) ($row['region'] ?? ''));
            if ($countryCode === 'ES') {
                $regionLower = function_exists('mb_strtolower') ? mb_strtolower($region) : strtolower($region);
                if ($regionLower !== '' && preg_match('/catal|barcel|girona|lleida|tarragon/i', $regionLower) === 1) {
                    $coordinates = [41.3874, 2.1686];
                }
            }

            $points[] = [
                'country_code' => $countryCode,
                'region' => $region !== '' ? $region : 'Desconegut',
                'total' => (int) ($row['total'] ?? 0),
                'lat' => $coordinates[0],
                'lng' => $coordinates[1],
            ];
        }

        return $points;
    }

    private function splitStoredProjectRoleNames(string $storedRoleNames): array
    {
        $roleNames = array_filter(array_map('trim', explode('||', $storedRoleNames)), static fn (string $roleName): bool => $roleName !== '');

        return array_values(array_unique($roleNames));
    }
}
