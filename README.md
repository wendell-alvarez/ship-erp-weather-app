## HOW TO RUN THE PROJECT
    1. Clone the repository
    2. populate the WEATHERMAP_API_KEY on .env, WEATHERMAP_ENDPOINT is prepopulated from .env.example
    3. Run "composer install"
    4. php artisan key:generate
    5. Run "php artisan serve"

## HOW TO RUN TEST
    1. Run "php artisan test"

## VERSIONS USED
    PHP 8.4.1
    COMPOSER 2.8.12
    LARAVEL FRAMEWORK 12.42.0
    Laravel Installer 5.23.2
    OS: UBUNTU 24.04.3 LTS
# SHORT EXPLANATION

___Routing___ - since the routes will be used for API, I moved the API routes to a seperate file ___routes/api.php___.

I included a API version ___v1___ to future proof the routes incase ___v2___ is introduced making sure those using ___v1___ api call won't break and cause errors. Added a RateLimiter middleware that can be found on ___AppServiceProvider___.

___WeatherController___ - instantiate __WeatherMapService__, on method ___getWeather___ it accepts two parameters $city and $useCache, validation for $city is included. If validation fails it returns 400 status code. ___WeatherMapService__ function ___getWeather___ is used and 2 parameters is passed $city and $useCache which returns JsonResponse. Exception is thrown when an error occured on __WeatherMapService__. Method ___getCachedWeather___ is reusing the ___getWeather___ method returning the cached JsonResponse result.

___WeatherMap___ service is placed in its own file to ensure all WeatherMap-related logic is separated, organized, and easier to maintain.

**Methods:**

    1. getWeather - accepts two parameters $city and $useCache, returns JsonResponse, $cacheKey for city's weather cache key.  
    Checks if $useCache is true and if there is an existing cache for that $city given it returns the cached result.  
    When no cached is return we do an GET API call on the endpoint route and pass the city and api key.  
    If Unsuccessful we return JsonResponse with the given error code. If api call is a success we extract the data and save it to cache.  
    If $useCache is true we save it to cache else we return the extracted data.  
      

    2. handleSuccess - extracts the data and assigns it to specific array keys.  
    if $useCache is true it saves the date to the cache and assigns 'cached' value to array key 'source'.  
    if $useCache is false we return the data from the API call and assigns 'external' value to array key 'source'.  
      
    3. formatResponse - this method formats the source that's assigned based on $useCache.  
    this also returns the JsonResponse with status code.  
      
    4.  error - returns error JsonResponse with corresponding message and status code.  
      
    5. handleError - returns error code and error message to error method.

**Imports:**   
use Carbon\Carbon;  
use Exception;  
use Illuminate\Http\JsonResponse;  
use Illuminate\Support\Facades\Cache;  
use Illuminate\Support\Facades\Http;

**Testing**

WeatherMapTest:  
2 constant variable are being reused.  
const **TOKYO** = 'Tokyo';  
const **TEST_CITY** = 'TestCity';  

**Methods**

    1. setUp - ensures that the test run smoothly and also clears the cache.
    2. getWeatherUrl - reusable method for getting the url.
    3. Other methods are ensuring that certain status code and values are returned matchin the expected result.
    4. Rate limiting is also included in the test.
    5. Using the real API ensures API keys and endpoints are working.
    6. Also included a fake response using Http::fake
**Weather map config file**  
returns API_KEY and ENDPOINT, this references WEATHERMAP_API_KEY and WEATHERMAP_ENDPOINT on the .env file.  
**Caching**  
cached is saved on the db using sqlite