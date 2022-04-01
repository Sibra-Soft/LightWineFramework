<?php
namespace LightWine\Modules\ServiceProvider\Services;

use LightWine\Core\Helpers\HttpContextHelpers;
use LightWine\Modules\Resources\Services\ResourceService;
use LightWine\Core\Models\PageModel;
use LightWine\Providers\TemplateProvider\Services\TemplateProviderService;
use LightWine\Core\Services\ComponentsService;
use LightWine\Providers\JsonProvider\Services\JsonProviderService;
use LightWine\Providers\ModuleProvider\Services\ModuleProviderService;
use LightWine\Modules\Api\Services\ApiService;
use LightWine\Providers\PartialProvider\Services\PartialProviderService;
use LightWine\Core\Helpers\StringHelpers;
use LightWine\Modules\Scheduler\Services\SchedulerService;
use LightWine\Providers\Imdb\Services\ImdbApiProviderService;
use LightWine\Core\Helpers\RequestVariables;
use LightWine\Modules\Files\Services\ImageFileService;
use LightWine\Core\HttpResponse;
use LightWine\Modules\Resources\Enums\ResourceTypeEnum;

class ServiceProviderService
{
    private ImageFileService $imageFileService;
    private TemplateProviderService $templateProviderService;
    private ComponentsService $componentService;
    private JsonProviderService $jsonProviderService;
    private ModuleProviderService $moduleProviderService;
    private PartialProviderService $partialProviderService;
    private ApiService $apiService;
    private SchedulerService $scheduler;
    private ImdbApiProviderService $imdbProviderService;

    public function __construct(){
        $this->templateProviderService = new TemplateProviderService();
        $this->componentService = new ComponentsService();
        $this->jsonProviderService = new JsonProviderService();
        $this->moduleProviderService = new ModuleProviderService();
        $this->partialProviderService = new PartialProviderService();
        $this->apiService = new ApiService();
        $this->scheduler = new SchedulerService();
        $this->imdbProviderService = new ImdbApiProviderService();
        $this->imageFileService = new ImageFileService();
    }

    public function CheckForServiceRequest($requestUri): PageModel {
        $pageModel = new PageModel;

        switch ($requestUri) {
            case "/resources.dll": $this->GetResources(RequestVariables::Get("filename"), RequestVariables::Get("type"), (bool)RequestVariables::Get("single")); break;
            case "/images.dll": $this->GetImage(); break;
            case "/template.dll": $pageModel->Content = $this->GetTemplate(); break;
            case "/component.dll": $pageModel->Content = $this->GetComponent(); break;
            case "/logoff.dll": HttpContextHelpers::Logoff(); break;
            case "/json.dll": $pageModel->Content = $this->GetJson($pageModel); break;
            case "/module.dll": $pageModel->Content = $this->GetModule(); break;
            case "/api.dll": $pageModel = $this->GetApiCall(); break;
            case "/partial.dll": $pageModel->Content = $this->GetPartial(); break;
            case "/scheduler.dll": $pageModel->Content = $this->scheduler->CheckForScheduledEvents(); break;
            case "/imdb.dll": $pageModel->Content = $this->GetFromImdb(); break;
            case "/app-config.json": HttpContextHelpers::Logoff(); break;

            default: $pageModel->Content = "";
        }

        return $pageModel;
    }

    /**
     * This function gets a resource file for example: Javascript or CSS
     * @param string $filename
     * @param string $type
     * @param bool $single
     */
    private function GetResources(string $filename, string $type, bool $single){
        $resources = new ResourceService();
        $resourceContent = $resources->GetResourcesBasedOnFilename($filename, $type, $single);

        HttpResponse::SetHeader("Pragma", "public");
        HttpResponse::SetHeader("Cache-Control", "max-age=86400");
        HttpResponse::SetHeader("Expires", gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));

        if($type == ResourceTypeEnum::CSS) HttpResponse::SetContentType("text/css");
        if($type == ResourceTypeEnum::JS) HttpResponse::SetContentType("application/javascript");

        HttpResponse::SetData($resourceContent);
        exit();
    }

    private function GetImage(){
        $filename = RequestVariables::Get("filename");
        $image = $this->imageFileService->GetImageByName($filename);

        HttpResponse::$MinifyHtml = false;

        if(!$image->Found){
            HttpResponse::ShowError(404, "The requested file could not be found", "File not found");
        }

        if(!$image->Permission){
            HttpResponse::ShowError(403, "You don't have permission to access the requested content", "Forbidden");
        }

        HttpResponse::SetHeader("pragma", "public");
        HttpResponse::SetHeader("Cache-Control", "max-age=86400");
        HttpResponse::SetHeader("Expires", gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));

        HttpResponse::SetContentType($image->ContentType);
        HttpResponse::SetData($image->FileData);
        exit();
    }

    private function GetTemplate(): string {
        return $this->templateProviderService->HandleTemplateRequest();
    }

    private function GetComponent(): string {
        return $this->componentService->HandleRenderComponent(RequestVariables::Get("name"));
    }

    private function GetJson(PageModel $page): string {
        return $this->jsonProviderService->HandleJsonRequest($page);
    }

    private function GetModule(): string {
        $moduleResponse = $this->moduleProviderService->RunModule(RequestVariables::Get("name"));

        if(StringHelpers::IsNullOrWhiteSpace($moduleResponse)){
            exit();
        }else{
            return $moduleResponse;
        }
    }

    private function GetApiCall(): PageModel {
        return $this->apiService->Start();
    }

    private function GetPartial(): string {
        return $this->partialProviderService->HandlePartialRequest();
    }

    private function GetFromImdb(): string {
        $searchMovieValue = RequestVariables::Get("search-movie");
        $searchSerieValue = RequestVariables::Get("search-serie");
        $titleId = RequestVariables::Get("title");
        $seasonNr = RequestVariables::Get("season");

        header('Content-Type: application/json; charset=utf-8');
        if(!StringHelpers::IsNullOrWhiteSpace($searchMovieValue)){
            return json_encode($this->imdbProviderService->SearchMovie($searchMovieValue));
        }else{
            if(!StringHelpers::IsNullOrWhiteSpace($searchSerieValue)){
                return json_encode($this->imdbProviderService->SearchSerie($searchSerieValue));
            }else{
                if(!StringHelpers::IsNullOrWhiteSpace($seasonNr)){
                    return json_encode($this->imdbProviderService->GetSerieSeasonEpisodes($titleId, $seasonNr));
                }else{
                    return json_encode($this->imdbProviderService->GetTitleBasedOnImdbId($titleId));
                }
            }
        }
    }
}