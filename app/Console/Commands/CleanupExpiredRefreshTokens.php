<?php

namespace App\Console\Commands;

use App\Repositories\RefreshToken\RefreshTokenInterface;
use Illuminate\Console\Command;

class CleanupExpiredRefreshTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:cleanup-refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired refresh tokens from database';

    protected $refreshTokenRepository;

    public function __construct(RefreshTokenInterface $refreshTokenRepository)
    {
        parent::__construct();
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Cleaning up expired refresh tokens...');

        $deletedCount = $this->refreshTokenRepository->deleteExpiredTokens();

        $this->info("Successfully deleted {$deletedCount} expired refresh tokens.");

        return Command::SUCCESS;
    }
}
