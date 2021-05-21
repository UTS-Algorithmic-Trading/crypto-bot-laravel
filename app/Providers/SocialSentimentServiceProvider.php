<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Google\Cloud\Core\ServiceBuilder;
use Google\Cloud\Language\LanguageClient;


class SocialSentimentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    public function getSentiment($text)
    {
        // Authenticate using a keyfile path
        $cloud = new ServiceBuilder([
            'keyFilePath' => config('services.google_cloud.key_file'),
            'projectId' => config('services.google_cloud.project_name'),
        ]);

        //Get an instance of the language class, uising the authenticated ServiceBuilder.
        //See: https://www.chowles.com/sentiment-analysis-using-laravel-and-google-natural-language-api/
        //I couldn't find any other way of referencing the ServiceBuilder.
        //Most examples use: $language = new LanguageClient();
        $language = $cloud->language();

        // Analyze a sentence.
        $annotation = $language->analyzeSentiment($text);
        return $annotation->sentiment();
    }
}
