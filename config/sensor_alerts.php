<?php

return [
    'amonia' => (float) env('ALERT_AMONIA_THRESHOLD', 25),
    'thi' => (float) env('ALERT_THI_THRESHOLD', 83),
    'cooldown_minutes' => (int) env('ALERT_COOLDOWN_MINUTES', 30),
];
