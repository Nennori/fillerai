<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ai extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:run {--gameServer=} {--gameId=} {--playerId=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $gameServer = $this->option('gameServer');
        $gameId = $this->option('gameId');
        $playerId = (integer)$this->option('playerId');

        $response = Http::withHeaders(['Accept' => 'application/json'])->get($gameServer . '/api/game/' . $gameId);
        $game = $response->json();

        if ($game['winnerPlayerId'] !== 0) {
            exit((string)$game['winnerPlayerId']);
        }
        $width = $game['field']['width'];
        $height = round($game['field']['height'] / 2, 0, PHP_ROUND_HALF_DOWN);
        $i = $playerId === 1 ? ($width - 1) * ($height - 1) + ($width) * ($height + 1) - 1 : $width - 1;
        $colors = [
            'current' => [
                'color' => $game['players'][$playerId]['color'],
                'count' => 0,
            ],
            'next' => [
                'curColor' => '',
                'prevColor' => '',
                'count' => 0
            ],
            'opponent' => [
                'color' => $game['players'][$playerId % 2 + 1]['color'],
                'count' => 0,
            ],
        ];
        $this->findPlayerCells($game, $i, $width, $height, $playerId, $colors);
        Http::put($gameServer . '/api/game/' . $gameId, ['playerId'=>$playerId, 'color'=>$colors['next']['prevColor']]);
        exit('0');
    }

    private function findPlayerCells(array &$game, int $i, int $width, int $height, int $playerId, array &$colors)
    {
        if ($i < 0 || $i >= ($width - 1) * ($height - 1) + ($width) * ($height + 1) || $game['field']['cells'][$i]['playerId'] === $playerId % 2 + 1) {
            return;
        }
        if ($game['field']['cells'][$i]['color'] !== $colors['current']['color']) {
            $colors['next']['curColor'] = $game['field']['cells'][$i]['color'];
            $count = $this->findColors($game, $i, $width, $height, $playerId, $colors);
            if ($count >= $colors['next']['count']) {
                $colors['next']['prevColor'] = $colors['next']['curColor'];
                $colors['next']['count'] = $count;
            }
            return;
        }
        $colors['current']['count'] += 1;
        $this->findPlayerCells($game, $i + $width, $width, $height, $playerId, $colors);
        $this->findPlayerCells($game, $i - $width, $width, $height, $playerId, $colors);
        $playerId === 1 ? $this->findPlayerCells($game, $i - $width + 1, $width, $height, $playerId, $colors) : $this->findPlayerCells($game, $i + $width - 1, $width, $height, $playerId, $colors);
    }

    private function findColors(array $game, int $i, int $width, int $height, int $playerId, array $colors)
    {
        $count = 1;
        if ($i < 0 || $i >= ($width - 1) * ($height - 1) + ($width) * ($height + 1)) {
            return 0;
        }
        if ($game['field']['cells'][$i]['color'] !== $colors['next']['curColor'] || $game['field']['cells'][$i]['color'] === $colors['current']['color']) {
            return 0;
        }
        $count += $this->findColors($game, $i + $width, $width, $height, $playerId, $colors);
        $count += $this->findColors($game, $i - $width, $width, $height, $playerId, $colors);
        $playerId === 1 ? $this->findColors($game, $i - $width + 1, $width, $height, $playerId, $colors) : $this->findColors($game, $i + $width - 1, $width, $height, $playerId, $colors);
        return $count;
    }

}
