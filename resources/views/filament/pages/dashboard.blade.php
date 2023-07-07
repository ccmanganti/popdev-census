<x-filament::page>
    <link rel="stylesheet" href="../styles.css">


    <h1 style="@if(!auth()->user()->hasRole('LGU')) display:none; @else display:block; @endif" class="suggested-title">Suggested Program for {{auth()->user()->name}}</h1>
    <div class="suggested" style="@if(!auth()->user()->hasRole('LGU')) display:none; @else display:flex; @endif">
        <h1 class="title">@if(auth()->user()->email == "angeles@gmail.com") Support to Education @elseif(auth()->user()->email == "mark@gmail.com") Health Services: Vaccination for infants/children. @elseif(auth()->user()->email == "sanjose_nuevaecija@gov.com.ph") Economic and Investment Promotion. @endif</h1>
        <img src="@if(auth()->user()->email == 'angeles@gmail.com') ../img/education.png @elseif(auth()->user()->email == 'mark@gmail.com') ../img/health.png @elseif(auth()->user()->email == 'sanjose_nuevaecija@gov.com.ph') ../img/economy.png @endif" alt="" class="program-icon">
    </div>
</x-filament::page>
