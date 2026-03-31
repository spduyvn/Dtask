<?php

namespace App\Providers;

use App\Repositories\Project\ProjectRepository;
use App\Repositories\Project\ProjectRepositoryInterface;
use App\Repositories\User\UserRepository;
use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\Task\UserTaskRepository;
use App\Repositories\Task\UserTaskRepositoryInterface;
use App\Repositories\Note\NoteRepository;
use App\Repositories\Note\NoteRepositoryInterface;
use App\Repositories\FlashCard\FlashCardRepository;
use App\Repositories\FlashCard\FlashCardRepositoryInterface;
use App\Repositories\FlashCardGroup\FlashCardGroupRepository;
use App\Repositories\FlashCardGroup\FlashCardGroupRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(ProjectRepositoryInterface::class, ProjectRepository::class);
        $this->app->bind(UserTaskRepositoryInterface::class, UserTaskRepository::class);
        $this->app->bind(NoteRepositoryInterface::class, NoteRepository::class);
        $this->app->bind(FlashCardRepositoryInterface::class, FlashCardRepository::class);
        $this->app->bind(FlashCardGroupRepositoryInterface::class, FlashCardGroupRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
