<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Monitoring;

class MonitoringSeeder extends Seeder
{
    public function run()
    {
        Monitoring::insert([
            [
                'name' => 'PRTG 1',
                'url'  => 'https://prtg.abhinawa.com',
                'icon' => 'heroicon-o-presentation-chart-bar',
                'desc' => 'Monitoring utama jaringan Abhinawa',
                'is_active' => 1
            ],
            [
                'name' => 'PRTG 2',
                'url'  => 'https://prtg2.abhinawa.com',
                'icon' => 'heroicon-o-presentation-chart-bar',
                'desc' => 'Secondary monitoring site',
                'is_active' => 1
            ],
            [
                'name' => 'Zabbix',
                'url'  => 'https://zabbix-triton.abhinawa.com',
                'icon' => 'heroicon-o-presentation-chart-bar',
                'desc' => 'Zabbix monitoring server',
                'is_active' => 1
            ],
        ]);
    }
}
