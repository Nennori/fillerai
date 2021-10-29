<?php
echo exec('php artisan ai:run --gameServer='.$argv[1].' --gameId='.$argv[2].' --playerId='.$argv[3]);
