<?php

namespace App\Filament\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use App\Models\PopulationRecord; 
use App\Models\Philprovince; 
use App\Models\Philmuni; 
use App\Models\Philbrgy; 
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Closure;
use Illuminate\Support\Facades\Route;

class DistributionOfPopulationByHighestEducationalAttainmentAndSex extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static string $chartId = 'distributionOfPopulationByHighestEducationalAttainmentAndSex';

    public static function canView(): bool
    {
        if ($currentPath= Route::getFacadeRoot()->current()->uri() == "/"){
            return false;
        } else {
            return true;
        }
    }
 

    protected static ?int $sort = 5; 

    protected static bool $deferLoading = true;

    protected int | string | array $columnSpan = 'full';
 
    protected static ?string $pollingInterval = null;

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'VIII. Distribution of Population by Highest Educational Attainment and Sex';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */

    protected function getFormSchema(): array
    {
        $user = auth()->user();
        if($user->hasRole('Barangay') || $user->hasRole('Enumerator')){
            return [

            ];
        }
        return [
            Select::make('province')
                ->reactive()
                ->default(function() use ($user){
                    if ($user->getRoleNames()->first() !== 'Superadmin'){
                        return Philprovince::where('provCode', '=', $user->province)->first()->provCode; 
                    } else {
                        return false;
                    }
                })
                ->label('Province Name')
                ->options(function() use ($user) {
                    if ($user->getRoleNames()->first() === 'LGU'){
                        return Philprovince::where('provCode', '=', $user->province)->pluck('provDesc', 'provCode'); 
                    } else if ($user->getRoleNames()->first() === 'Barangay'){
                        return Philprovince::where('provCode', '=', $user->province)->pluck('provDesc', 'provCode'); 
                    } else {
                        return Philprovince::all()->pluck('provDesc', 'provCode');
                    }
                }),
            Select::make('city_or_municipality')
                ->reactive()
                ->default(function() use ($user){
                    if ($user->getRoleNames()->first() !== 'Superadmin'){
                        return Philmuni::where('citymunCode', '=', $user->city_or_municipality)->first()->citymunCode; 
                    } else {
                        return false;
                    }   
                })
                ->disabled(fn (Closure $get) => ($get('province') == null))
                ->label('City/Municipality Name')
                ->options(function(callable $get) use ($user) {
                    if ($user->getRoleNames()->first() === 'LGU'){
                        return Philmuni::where('citymunCode', '=', $user->city_or_municipality)->pluck('citymunDesc', 'citymunCode'); 
                    } else if ($user->getRoleNames()->first() === 'Barangay'){
                        return Philmuni::where('citymunCode', '=', $user->city_or_municipality)->pluck('citymunDesc', 'citymunCode'); 
                    } else {
                        return Philmuni::where('provCode', '=', $get('province'))->pluck('citymunDesc', 'citymunCode');
                    }
                }),
            Select::make('barangay')
                ->reactive()
                ->label('Barangay Name')
                ->disabled(fn (Closure $get) => ($get('province') == null))
                ->options(function(callable $get) use ($user) {
                    if ($user->getRoleNames()->first() === 'LGU'){
                        return Philbrgy::where('citymunCode', '=', $user->city_or_municipality)->pluck('brgyDesc', 'brgyCode'); 
                    } else {
                        return Philbrgy::where('citymunCode', '=', $get('city_or_municipality'))->pluck('brgyDesc', 'brgyCode');
                    }
                })
        ];
    }

    public function getPREthnicityAndSex($ageAndSex) {
        $user = auth()->user();
        $json_data_count = 0;
        
        if($user->hasRole('Superadmin')){
            if($ageAndSex[2] == null && $ageAndSex[3] == null && $ageAndSex[4] == null){
                $json_data = PopulationRecord::all();
            } else if ($ageAndSex[2] != null && $ageAndSex[3] == null && $ageAndSex[4] == null){
                $json_data = PopulationRecord::where('province', '=', $ageAndSex[2])->get(); 
            } else if ($ageAndSex[2] != null && $ageAndSex[3] != null && $ageAndSex[4] == null){
                $json_data = PopulationRecord::where('city_or_municipality', '=', $ageAndSex[3])->get(); 
            } else if ($ageAndSex[2] != null && $ageAndSex[3] != null && $ageAndSex[4] != null){
                $json_data = PopulationRecord::where('barangay', '=', $ageAndSex[4])->get(); 
            }
                    
        } else if($user->hasRole('LGU')){
            if ($ageAndSex[2] != null && $ageAndSex[3] != null && $ageAndSex[4] == null){
                $json_data = PopulationRecord::where('city_or_municipality', '=', $user->city_or_municipality)->get(); 
            } else if ($ageAndSex[2] != null && $ageAndSex[3] != null && $ageAndSex[4] != null){
                $json_data = PopulationRecord::where('barangay', '=', $ageAndSex[4])->get(); 
            }
        } else if($user->hasRole('Barangay') || $user->hasRole('Enumerator')){
            $json_data = PopulationRecord::where('barangay', '=', $user->barangay)->get(); 
        }

        foreach($json_data as $json){
            foreach($json->individual_record as $data){
                if(($data['q3'] === $ageAndSex[1]) && ($data['q11'] === $ageAndSex[0])){
                    $json_data_count += 1;
                }
            }
        }    

        return $json_data_count;
    }

    protected function getOptions(): array
    {

        if (auth()->user()->hasRole('Barangay') || auth()->user()->hasRole('Enumerator')){
            $province = null;
            $city_or_municipality = null;
            $barangay = null;
            
        } else {
            $activeFilter = $this->filter;
            $province = $this->filterFormData['province'];
            $city_or_municipality = $this->filterFormData['city_or_municipality'];
            $barangay = $this->filterFormData['barangay'];    
        }

        if (!$this->readyToLoad) {
            return [];
        }
        
        sleep(1);

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 400,
                'stacked' => true,
            ],
            'series' => [
                [
                    'name' => 'Male',
                    'data' => [
                        $this->getPREthnicityAndSex(['0', '1', $province, $city_or_municipality, $barangay]),
                        $this->getPREthnicityAndSex(['1', '1', $province, $city_or_municipality, $barangay]),
                        $this->getPREthnicityAndSex(['2', '1', $province, $city_or_municipality, $barangay]),
                        $this->getPREthnicityAndSex(['3', '1', $province, $city_or_municipality, $barangay]),
                        $this->getPREthnicityAndSex(['4', '1', $province, $city_or_municipality, $barangay]),
                        $this->getPREthnicityAndSex(['5', '1', $province, $city_or_municipality, $barangay]),
                        $this->getPREthnicityAndSex(['6', '1', $province, $city_or_municipality, $barangay]),
                        $this->getPREthnicityAndSex(['7', '1', $province, $city_or_municipality, $barangay]),
                        $this->getPREthnicityAndSex(['8', '1', $province, $city_or_municipality, $barangay]),
                        $this->getPREthnicityAndSex(['9', '1', $province, $city_or_municipality, $barangay]),
                        $this->getPREthnicityAndSex(['10', '1', $province, $city_or_municipality, $barangay]),
                        $this->getPREthnicityAndSex(['11', '1', $province, $city_or_municipality, $barangay]),
                        $this->getPREthnicityAndSex(['12', '1', $province, $city_or_municipality, $barangay]),
                        $this->getPREthnicityAndSex(['13', '1', $province, $city_or_municipality, $barangay]),
                    ],
                ],
                [
                    'name' => 'Female',
                    'data' => [
                        -$this->getPREthnicityAndSex(['0', '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPREthnicityAndSex(['1', '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPREthnicityAndSex(['2', '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPREthnicityAndSex(['3', '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPREthnicityAndSex(['4', '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPREthnicityAndSex(['5', '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPREthnicityAndSex(['6', '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPREthnicityAndSex(['7', '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPREthnicityAndSex(['8', '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPREthnicityAndSex(['9', '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPREthnicityAndSex(['10', '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPREthnicityAndSex(['11', '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPREthnicityAndSex(['12', '2', $province, $city_or_municipality, $barangay]),
                        -$this->getPREthnicityAndSex(['13', '2', $province, $city_or_municipality, $barangay]),
                    ],
                ],
            ],
            'xaxis' => [
                'categories' => [
                    'No Education',
                    'Pre-School',
                    'Elementary Level',
                    'Elementary Graduate',
                    'HS Level',
                    'HS Graduate',
                    'JHS Level',
                    'JHS Graduate',
                    'SHS Level',
                    'SHS Graduate',
                    'Vocational/Tech',
                    'College Level',
                    'College Graduate',
                    'Post-Graduate',
                ],
                'labels' => [
                    'style' => [
                        'colors' => '#9ca3af',
                        'fontWeight' => 600,
                    ],
                ],
                'title' => [
                    'text' => 'Population Comparison',
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'colors' => '#9ca3af',
                        'fontWeight' => 600,
                    ],
                ],
                'title' => [
                    'text' => 'Highest Educational Attainment',
                ],
            ],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 3,
                    'horizontal' => true,
                    'barHeight' => '85%',
                    'dataLabels' => [
                        'total' => [
                            'enabled' => false,
                        ],
                        'position' => 'top'

                    ]
                ],
            ],
            'legend' => [
                'position' => 'top'
            ],
            'colors' => ['#4245db', '#db42ad'],
        ];
    }
}
