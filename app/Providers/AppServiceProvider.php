<?php

namespace App\Providers;

use App\Assistant\Runner;
use App\Finance\Ai\Classifier;
use App\Finance\Ai\ClaudeClassifier;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(Classifier::class, fn (): ClaudeClassifier => new ClaudeClassifier(
            apiKey: (string) config('ai.key'),
            model: (string) config('ai.model'),
        ));

        $this->app->bind(Runner::class, fn (): Runner => new Runner(
            apiKey: (string) config('ai.key'),
            // Quello della chat, non quello che classifica: vedi config/ai.php.
            model: (string) (config('ai.assistant_model') ?: config('ai.model')),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
