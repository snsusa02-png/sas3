<?php

return [

    /*
     * Database related configurations.
     */
    'database' => [
        /*
         * Name of the tables created by the migrations
         * and used by the models of this package.
         */
        'tables' => [
            'surveys' => 'surveys',
            'sections' => 'srvy_sections',
            'questions' => 'srvy_questions',
            'entries' => 'srvy_entries',
            'answers' => 'srvy_answers',
        ],
    ],
];
