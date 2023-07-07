<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\PopulationRecord; 

class StatsOverview extends BaseWidget
{
    public bool $readyToLoad = false;
    protected static ?string $pollingInterval = null;
    public function loadData()
    {
        $this->readyToLoad = true;
    }

    protected function getCards(): array
    {

        if (! $this->readyToLoad) {
            return [
                Card::make('Total', 'loading...'),
            ];
        }

        sleep (2);

        $maleData = $this->getDescMale();
        $femaleData = $this->getDescFemale();

        if(auth()->user()->hasRole('Enumerator')){
            return [
                Card::make('Total No. of Households Encoded', PopulationRecord::where('encoder_name', '=', auth()->user()->name)->count().'/'.auth()->user()->household_quota)
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color($this->enumeratorQuota()[1])
                ->description($this->enumeratorQuota()[0]),
                Card::make('Household Records Encoded Today', PopulationRecord::whereDate('created_at', date('Y-m-d'))->where('encoder_name', '=', auth()->user()->name)->count())
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),
            ];
        } else {
            return [
                Card::make('Total Population', $this->getDataSex(null))
                    ->color('secondary'),
                Card::make('Total No. of Household', $this->getDataSex('HH'))
                    ->color('secondary'),
                Card::make('Total Number of Males', $this->getDataSex('1'))
                    ->description($maleData[0])
                    ->descriptionIcon($maleData[1])
                    ->color($maleData[2])
                    ->chart($maleData[3]),
                Card::make('Total Number of Females', $this->getDataSex('2'))
                    ->description($femaleData[0])
                    ->descriptionIcon($femaleData[1])
                    ->color($femaleData[2])
                    ->chart($femaleData[3]),
            ];
        }
    }

    // CUSTOM FUNCTIONS

    public function enumeratorQuota(){
        if((int)auth()->user()->household_quota < PopulationRecord::where('encoder_name', '=', auth()->user()->name)->count()){
            return ["With ".PopulationRecord::where('encoder_name', '=', auth()->user()->name)->count()-(int)auth()->user()->household_quota." excess records", 'danger'];
        } else if((int)auth()->user()->household_quota == PopulationRecord::where('encoder_name', '=', auth()->user()->name)->count()){
            return ["Quota of ".(int)auth()->user()->household_quota." completed", 'success'];
        } 
        else {
            return ["With ".(int)auth()->user()->household_quota-PopulationRecord::where('encoder_name', '=', auth()->user()->name)->count()." records left on quota", 'primary'];
        }
    }

    public function getDescMale(){
        if($this->getDataSex('1') > $this->getDataSex('2')){
            return [((int)$this->getDataSex('1') - (int)$this->getDataSex('2')).' greater than Females', 'heroicon-s-trending-up', 'success', [2, 4, 8, 16, 32, 64, 128]];
        } else if($this->getDataSex('1') < $this->getDataSex('2')) {
            return [((int)$this->getDataSex('2') - (int)$this->getDataSex('1')).' less than Females', 'heroicon-s-trending-down', 'danger', [128, 64, 32, 16, 8, 4, 2]]; 
        } else {
            return ['M&F population are equal', 'heroicon-o-arrow-right', 'secondary', [7, 2, 10, 3, 15, 4, 17]]; 
        }
    }

    public function getDescFemale(){

        if($this->getDataSex('2') > $this->getDataSex('1')){
            return [((int)$this->getDataSex('2') - (int)$this->getDataSex('1')).' greater than Males', 'heroicon-s-trending-up', 'success', [7, 2, 10, 5, 15, 7, 25]];
        } else if($this->getDataSex('2') < $this->getDataSex('1')) {
            return [((int)$this->getDataSex('1') - (int)$this->getDataSex('2')).' less than Males', 'heroicon-s-trending-down', 'danger', [128, 64, 32, 16, 8, 4, 2]]; 
        } else {
            return ['M&F population are equal', 'heroicon-o-arrow-right', 'secondary', [7, 2, 10, 3, 15, 4, 17]]; 
        }
    }
    

    public function getDataSex($sex){
        $user = auth()->user();
        $json_data_count = 0;

        if($user->hasRole('Superadmin')){
            if($user->province == null && $user->city_or_municipality == null && $user->barangay == null){
                $json_data = PopulationRecord::all();
            } else if ($user->province != null && $user->city_or_municipality == null && $user->barangay == null){
                $json_data = PopulationRecord::where('province', '=', $user->province)->get(); 
            } else if ($user->province != null && $user->city_or_municipality != null && $user->barangay == null){
                $json_data = PopulationRecord::where('city_or_municipality', '=', $user->city_or_municipality)->get(); 
            } else if ($user->province != null && $user->city_or_municipality != null && $user->barangay != null){
                $json_data = PopulationRecord::where('barangay', '=', $user->barangay)->get(); 
            }
                    
        } else if($user->hasRole('LGU')){
            if ($user->province != null && $user->city_or_municipality != null && $user->barangay == null){
                $json_data = PopulationRecord::where('city_or_municipality', '=', $user->city_or_municipality)->get(); 
            } else if ($user->province != null && $user->city_or_municipality != null && $user->barangay != null){
                $json_data = PopulationRecord::where('barangay', '=', $user->barangay)->get(); 
            }
        } else if($user->hasRole('Barangay') || $user->hasRole('Enumerator')){
            $json_data = PopulationRecord::where('barangay', '=', $user->barangay)->get(); 
        }

        foreach($json_data as $json){
            if ($sex == 'HH'){
                $json_data_count += 1;
            } else {
                foreach($json->individual_record as $data){
                    if($sex){
                        if($data['q3'] == $sex){
                            $json_data_count += 1;
                        }
                    } else {
                        $json_data_count += 1;
                    }
                    
                }
            }
        }

        return $json_data_count;
    }
}
