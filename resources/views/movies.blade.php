<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <title>Ziix</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet"/>
    <link href="/css/app.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.css"
          integrity="sha512-8D+M+7Y6jVsEa7RD6Kv/Z7EImSpNpQllgaEIQAtqHcI0H6F4iZknRj0Nx1DCdB+TwBaS+702BGWYC0Ze2hpExQ=="
          crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm"
            crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
            integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
            crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.min.js"
            integrity="sha384-Rx+T1VzGupg4BHQYs2gCW9It+akI2MM/mndMCy36UVfodzcJcF0GGLxZIzObiEfa"
            crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"
            integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.js"
            integrity="sha512-zlWWyZq71UMApAjih4WkaRpikgY9Bz1oXIW5G0fED4vk14JjGlQ1UmkGM392jEULP8jbNMiwLWdM8Z87Hu88Fw=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>
<body class="bg-dark text-bg-dark">
@if($settings->paused)
    <div class="bg-danger">
        <div class="text-center">Indexing is paused</div>
    </div>
@endif
<nav class="navbar bg-dark border-bottom border-body" data-bs-theme="dark">
    <div class="container-fluid">
        <a class="navbar-brand">Ziix</a>
        <form class="d-flex" role="search">
            <input class="form-control form-control-sm me-2" type="search" placeholder="Search" aria-label="Search">
            <button class="btn btn-outline-success btn-sm me-2" type="submit">
                <span class="align-middle material-symbols-outlined">search</span>
            </button>
            <a class="btn btn-outline-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#settingsModal"
               role="button">
                <span class="align-middle material-symbols-outlined">settings</span>
            </a>
            @if($settings->paused)
                <a class="ml-3 btn btn-outline-success btn-sm" id="resumeJobs">
                    <span class="align-middle material-symbols-outlined">play_arrow</span>
                </a>
            @else
                <a class="btn btn-outline-secondary btn-sm" id="pauseJobs">
                    <span class="align-middle material-symbols-outlined">pause</span>
                </a>
            @endif
        </form>
    </div>
</nav>
<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-md-12">
            <ul class="nav nav-tabs" id="moviesTab" role="tablist" data-bs-theme="dark">
                @foreach($folders as $key => $folder)
                    @if($folder->status == 1)
                        @php($name = \Illuminate\Support\Str::of($folder->path)->explode("/")->last())
                        <li class="nav-item" role="presentation">
                            <button class="nav-link @if($key == 0) active @endif" id="{{$folder->id}}-tab"
                                    data-bs-toggle="tab" data-bs-target="#{{$folder->id}}-tab-pane" type="button"
                                    role="tab" aria-controls="{{$folder->id}}-tab-pane" aria-selected="true">
                                {{ \Illuminate\Support\Str::ucfirst($name) }}</button>
                        </li>
                    @endif
                @endforeach
                <li class="nav-item position-relative" role="presentation">
                    <button class="nav-link" id="failed-tab" data-bs-toggle="tab" data-bs-target="#failed-tab-pane"
                            type="button" role="tab" aria-controls="failed-tab-pane" aria-selected="true">Log
                        @if($moviesfailed)
                            <span
                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{$moviesfailed}}</span>
                        @endif
                    </button>
                </li>
            </ul>
        </div>
        <div class="col-md-12">
            <div class="tab-content" id="moviesTabContent">
                @foreach($folders as $key => $folder)
                    <div class="tab-pane  @if($key == 0) active show @endif fade" id="{{$folder->id}}-tab-pane"
                         role="tabpanel" aria-labelledby="{{$folder->id}}-tab" tabindex="0">
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <ul class="nav float-end d-flex" id="moviesTab" role="tablist" data-bs-theme="dark">
                                    <li class="flex-grow">
                                        <div class="btn-group btn-group-sm" role="group"
                                             aria-label="Small button group">
                                            <a href="?order=title&direction={{$direction}}" type="button"
                                               class="btn btn-outline-@if($order == 'title'){{"success"}}@else{{"primary"}}@endif">Title</a>
                                            <a href="?order=meta.year&direction={{$direction}}"
                                               class="btn btn-outline-@if($order == 'meta.year'){{"success"}}@else{{"primary"}}@endif">Year</a>
                                            <a href="?order=meta.width&direction={{$direction}}"
                                               class="btn btn-outline-@if($order == 'meta.width'){{"success"}}@else{{"primary"}}@endif">Quality</a>
                                            <a href="?order={{$order}}&direction=@if($direction == 'asc'){{"desc"}}@else{{"asc"}}@endif"
                                               class="btn btn-outline-secondary">@if($direction == 'asc')
                                                    {{"DESC"}}
                                                @else
                                                    {{"ASC"}}
                                                @endif</a>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="row mt-2">
                            @foreach($movies as $movie)
                                @if($movie->folders_id == $folder->id && $movie->parent_id == 0)
                                    <div class="col-6 col-sm-4 col-lg-3 col-xl-2 col-xxl-1 overflow-y-hidden view"
                                         data-id="{{$movie->id}}" data-bs-toggle="offcanvas"
                                         data-bs-target="#viewMovie">
                                        <div class="poster position-relative">
                                            <span class="position-absolute badge text-bg-danger bottom-0 start-0 fs-8">{{$movie->meta->width ?? 0}}/{{$movie->meta->height ?? 0}}px</span>
                                            <span
                                                class="position-absolute badge text-bg-secondary bottom-0 end-0 fs-8">{{$movie->meta->year ?? 0}}</span>
                                            <img src="{{$movie->tmdb->poster}}" class="img-fluid">
                                        </div>
                                        <small class="text-truncate">{{$movie->title}}</small>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <div class="tab-pane fade" id="failed-tab-pane" role="tabpanel" aria-labelledby="failed-tab"
                     tabindex="0">
                    <div class="row mt-2">
                        <div class="col-12">
                            <table class="table table-sm" id="logTable" data-bs-theme="dark">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Movie</th>
                                    <th>Filename</th>
                                    <th>State</th>
                                    <th>Success</th>
                                    <th>Error</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <ul class="nav float-end d-flex" id="moviesTab" role="tablist" data-bs-theme="dark">
                                    <li class="flex-grow">
                                        <div class="btn-group btn-group-sm" role="group"
                                             aria-label="Small button group">
                                            <button class="btn btn-outline-primary" id="loadMoreLogs">Load more</button>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" data-bs-theme="dark" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="settingsModalLabel">Settings</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <legend class="mt-2">Ziix</legend>
                <div class="row">
                    <div class="col-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input settingCheckbox" type="checkbox" role="switch"
                                   id="flexSwitchFilesIdentifier" value="1" data-key="files_identifier"
                                   @if($settings->files_rename) checked @endif>
                            <label class="form-check-label" for="flexSwitchFilesIdentifier">Identify files</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input settingCheckbox" type="checkbox" role="switch"
                                   id="flexSwitchFilesRename" value="1" data-key="files_rename"
                                   @if($settings->files_rename) checked @endif>
                            <label class="form-check-label" for="flexSwitchFilesRename">Rename files
                                (name.year.quality.extension)</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input settingCheckbox" type="checkbox" role="switch"
                                   id="flexSwitchFilesIndex" value="1" data-key="files_index"
                                   @if($settings->files_index) checked @endif>
                            <label class="form-check-label" for="flexSwitchFilesIndex">Index files</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <label for="formControlInputFFmpegpath" class="form-label">FFmpeg path</label>
                        <input class="form-control settingInput" id="formControlInputFFmpegpath"
                               value="{{$settings->ffmpeg_path ?? ''}}" type="text" data-key="ffmpeg_path"
                               placeholder="ffmpeg path" aria-label="">
                        <label for="formControlInputFFProbepath" class="form-label">FFprobe path</label>
                        <input class="form-control settingInput" id="formControlInputFFProbepath"
                               value="{{$settings->ffprobe_path ?? ''}}" type="text" data-key="ffprobe_path"
                               placeholder="ffprobe path" aria-label="">
                    </div>
                </div>
                <legend class="mt-2">themoviedb.org</legend>
                <div class="row">
                    <div class="col-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input settingCheckbox" type="checkbox" role="switch"
                                   id="flexSwitchTMDBIndex" value="1" data-key="tmdb_index"
                                   @if($settings->files_index) checked @endif>
                            <label class="form-check-label" for="flexSwitchTMDBIndex">Lookup movies on
                                themoviedb.org</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input settingCheckbox" type="checkbox" role="switch"
                                   id="flexSwitchTMDBRename" value="1" data-key="tmdb_rename"
                                   @if($settings->files_index) checked @endif>
                            <label class="form-check-label" for="flexSwitchTMDBRename">Rename files with name from
                                themoviedb.org</label>
                            <label class="form-check-label"
                                   for="flexSwitchTMDBRename">(name.year.quality.extension)</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <label for="formControlInputTMDBToken" class="form-label">API Token - themoviedb.org</label>
                        <input class="form-control settingInput" id="formControlInputTMDBToken"
                               value="{{$settings->tmdb_token ?? ''}}" type="text" data-key="tmdb_token"
                               placeholder="themoviedb.org API Token" aria-label="">
                    </div>
                </div>
                <legend class="mt-2">Plex</legend>
                <div class="row">
                    <div class="col-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input settingCheckbox" type="checkbox" role="switch"
                                   id="flexSwitchPlexEnabled" value="1" data-key="plex_enabled"
                                   @if($settings->plex_enabled ?? 0) checked @endif>
                            <label class="form-check-label" for="flexSwitchPlexEnabled">Enable Plex</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input settingCheckbox" type="checkbox" role="switch"
                                   id="flexSwitchPlexLibraryUpdate" value="1" data-key="plex_library_update"
                                   @if($settings->plex_library_update ?? 0) checked @endif>
                            <label class="form-check-label" for="flexSwitchPlexLibraryUpdate">Enable library
                                updates</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <label for="formControlInputPlexUrl" class="form-label">Plex - Url</label>
                        <input class="form-control settingInput" id="formControlInputPlexUrl"
                               value="{{$settings->plex_url ?? ''}}" type="text" data-key="plex_url"
                               placeholder="Plex url" aria-label="">
                        <label for="formControlInputPlexToken" class="form-label">Plex - API Token</label>
                        <input class="form-control settingInput" id="formControlInputPlexToken"
                               value="{{$settings->plex_token ?? ''}}" type="text" data-key="plex_token"
                               placeholder="Plex Token" aria-label="">
                    </div>
                </div>
                <legend class="mt-2">Libraries</legend>
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Path</span>
                        <span>Plex</span>
                        <span>Actions</span>
                    </li>
                    @foreach($folders as $folder)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            @if($folder->status == 1)
                                <span>{{$folder->path}} <span class="badge bg-success rounded-pill">OK</span></span>
                            @endif
                            @if($folder->status == 0)
                                <span>{{$folder->path}} <span
                                        class="badge bg-secondary rounded-pill">Paused</span></span>
                            @endif
                            @if($folder->status == -1)
                                <span>{{$folder->path}} <span
                                        class="badge bg-secondary rounded-pill">Folder not found</span></span>
                            @endif
                            <span>
                                    @foreach($plex_libraries->Directory as $plex_library)
                                    @if($plex_library->attributes()->key == $folder->plex_key)
                                        {{$plex_library->Location->attributes()->path}}
                                    @endif
                                @endforeach
                                </span>
                            <span>
                                    @if($folder->status == 1)
                                    <button type="button" data-id="{{$folder->id}}"
                                            class="deactivatePath btn btn-sm btn-secondary">Pause</button>
                                @endif
                                @if($folder->status == 0 || $folder->status == -1)
                                    <button type="button" data-id="{{$folder->id}}"
                                            class="activatePath btn btn-sm btn-success">Activate</button>
                                @endif
                                    <button type="button" data-id="{{$folder->id}}"
                                            class="deletePath btn btn-sm btn-danger">Delete</button>
                                </span>
                        </li>
                    @endforeach
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <input id="newPath" type="text" placeholder="Path to movies" class="form-control">
                        <select id="newPathType" class="form-control">
                            <option value="1">Movie</option>
                            <option value="3">TV Show</option>
                        </select>
                        <select id="newPlexKey" class="form-control">
                            @foreach($plex_libraries->Directory as $plex_library)
                                <option
                                    value="{{$plex_library->attributes()->key}}">{{$plex_library->Location->attributes()->path}}</option>
                            @endforeach
                            <option value="0">None</option>
                        </select>
                        <button type="button" id="createPath" class="btn btn-sm btn-success">Create</button>
                    </li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" data-bs-theme="dark" id="logModal" tabindex="-1" aria-labelledby="logModalLabel"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="logModalLabel">Log</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="logOutput">

                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<div class="offcanvas offcanvas-end" style="width:40%;" data-bs-backdrop="false" data-bs-keyboard="true"
     data-bs-theme="dark" tabindex="-1" id="viewMovie" aria-labelledby="viewMovieLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="viewLabel"></h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div id="viewContent">
            <div class="input-group mb-3">
                <input type="text" class="form-control" placeholder="Movie filename" id="viewFilename"
                       aria-label="Movie name" aria-describedby="renameMovie">
                <button class="btn btn-outline-secondary" type="button" data-id="" id="renameMovie">Rename</button>
            </div>
            <div class="input-group mb-3">
                <select class="form-select" id="viewFileFolder">
                    @foreach($folders as $folder)
                        <option value="{{$folder->id}}">{{ $folder->path }}</option>
                    @endforeach
                </select>
                <input type="text" class="form-control" placeholder="Movie filepath" id="viewPath"
                       aria-label="Movie path" aria-describedby="button-viewpath">
                <button class="btn btn-outline-secondary" type="button" data-id="" id="moveMovie">Move</button>
            </div>
            <div class="">
                <table class="table table-sm">
                    <thead>
                    <tr>
                        <th>Key</th>
                        <th>Value</th>
                    </tr>
                    </thead>
                    <tbody id="viewDataset">

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="/js/app.js" type="application/javascript"></script>
</body>
</html>
