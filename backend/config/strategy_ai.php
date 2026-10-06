<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Master kill switch
    |--------------------------------------------------------------------------
    |
    | When false, no LLM request is sent. Context building and deterministic
    | recommendations can still be inspected by authorized CricIntel users.
    |
    */
    'enabled' => env('STRATEGY_AI_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Local P14/P15 FastAPI service
    |--------------------------------------------------------------------------
    */
    'base_url' => env(
        'STRATEGY_AI_URL',
        env('PREDICTIVE_ANALYTICS_URL', 'http://127.0.0.1:8100')
    ),

    'timeout_seconds' => (int) env(
        'STRATEGY_AI_TIMEOUT',
        45
    ),

    /*
    |--------------------------------------------------------------------------
    | Grounding controls
    |--------------------------------------------------------------------------
    */
    'max_context_evidence' => (int) env(
        'STRATEGY_AI_MAX_EVIDENCE',
        220
    ),

    'max_question_length' => (int) env(
        'STRATEGY_AI_MAX_QUESTION_LENGTH',
        1200
    ),

    'store_raw_response' => (bool) env(
        'STRATEGY_AI_STORE_RAW_RESPONSE',
        true
    ),
];
