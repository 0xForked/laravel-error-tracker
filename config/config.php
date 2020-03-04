<?php

/*
 * You can place your custom package configuration in here.
 */
return [
    /**
     * This is the base url of your error tracker application. Don't append the api route after it.
     * It will automatically append "/api/report" after it with all the data regarding the error.
     *
     * This is meant to be used with https://github.com/polliedev/laravel-error-tracker-app
     * but you are free to make your own application with the data sent to the API.
     */
    'base_url' => 'https://laravel-error-tracker-app.test',

    /**
     * This is extra data that'll be sent to the app.
     * It should be an array with key-value pairs of information.
     * You are also able to provide a function as a key. It'll be resolved on the moment it's sending the data to the app.
     */
    'meta_data' => []
];
