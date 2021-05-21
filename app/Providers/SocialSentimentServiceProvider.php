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

        //ENV must be set: https://cloud.google.com/document-ai/docs/error-messages
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.config('services.google_cloud.key_file'));
        putenv('GOOGLE_CLOUD_PROJECT_NAME='.config('services.google_cloud.project_name'));

        //ddd(env('GOOGLE_APPLICATION_CREDENTIALS'));

        //Get an instance of the language class, uising the authenticated ServiceBuilder.
        //See: https://www.chowles.com/sentiment-analysis-using-laravel-and-google-natural-language-api/
        //I couldn't find any other way of referencing the ServiceBuilder.
        //Most examples use: $language = new LanguageClient();
        $language = $cloud->language();

        // Analyze a sentence.
        $annotation = $language->analyzeSentiment(html_entity_decode($text));
        return $annotation->sentiment();
    }
}
