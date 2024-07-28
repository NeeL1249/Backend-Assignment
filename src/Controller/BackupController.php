<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BackupController extends AbstractController
{
    #[Route('/api/backup', name: 'app_backup', methods: ['GET'])]
    public function index(): Response
    {
        // We can add authorization here to make sure only admin can download the backup
        $dbHost = $_ENV['DATABASE_HOST'];
        $dbPort = $_ENV['DATABASE_PORT'];
        $dbUser = $_ENV['DATABASE_USER'];
        $dbPass = $_ENV['DATABASE_PASS'];
        $dbName = $_ENV['DATABASE_NAME'];
        $backupFile = sprintf('backup.sql');

        $command = sprintf('mysqldump -h %s -P %s -u %s -p%s %s > %s', $dbHost, $dbPort, $dbUser, $dbPass, $dbName, $backupFile);

        exec($command);

        $response = new Response(file_get_contents($backupFile));

        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($backupFile) . '"');

        return $response;
    }
}
