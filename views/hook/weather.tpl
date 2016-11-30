<!-- Block weather -->
{if isset($zip_code) && $zip_code}
    <div id="weather_block_home" class="block local-weather">
        <h4>Current Weather for {if isset($data->name)}{$data->name},{/if} {$zip_code}</h4>
        <div class="block_content">
            <img class="weather-icon" src="http://openweathermap.org/img/w/{$data->weather[0]->icon}.png" />
            <div class="weather-summary">
                {$data->weather[0]->main}
                <br/>
                <span class="weather-desc">{$data->weather[0]->description}</span>
            </div>
            <div style="clear: both;"></div>
            <ul>
                <li>Temp: {$temp}&deg;</li>
                <li>Temp Min: {$temp_min}&deg;</li>
                <li>Temp Max: {$temp_max}&deg;</li>
            </ul>
        </div>
    </div>
{else}
    <span>Zip Code for weather module not set.</span>
{/if}
<!-- /Block weather -->