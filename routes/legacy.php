<?php

use App\Http\Controllers\LegacyController;
 
Route::any('{path}', LegacyController::class)->where('path', '.*');
