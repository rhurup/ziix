$(document).ready(function () {
    function success(text) {
        $.toast({
            heading: 'Success',
            text: text,
            icon: 'success',
            position: 'top-right',
            loader: true,
            loaderBg: '#0dc972',
            textColor: 'white',
            bgColor: '#198754'
        })
    }

    function error(text) {
        $.toast({
            heading: 'Error',
            text: text,
            icon: 'error',
            position: 'top-right',
            loader: true,
            loaderBg: '#f20a20',
            textColor: 'white',
            bgColor: '#dc3545'
        })
    }

    function loadLogTable(el, text) {
        $.each(text.jobs, function (index, row) {
            var successResponse = jQuery.parseJSON(row.success) ?? false;
            var errorResponse = jQuery.parseJSON(row.error) ?? false;
            let successBtn = ''
            let errorBtn = ''

            if (typeof successResponse == 'object' && successResponse.length > 0) {
                successBtn = '<button type="button" data-bs-toggle="modal" data-bs-target="#logModal" data-logoutput="' + encodeURIComponent(JSON.stringify(successResponse)) + '" class="showLog btn btn-sm btn-outline-success">Show</button>';
            }
            if (typeof errorResponse == 'object' && errorResponse.length > 0) {
                errorBtn = '<button type="button" data-bs-toggle="modal" data-bs-target="#logModal" data-logoutput="' + encodeURIComponent(JSON.stringify(errorResponse)) + '" class="showLog btn btn-sm btn-outline-danger">Show</button>';
            }
            $(el + " tbody").append(`<tr>
                <td>${row.id}</td>
                <td>${row.created_at}</td>
                <td>${row.type}</td>
                <td>${row.movie.title}</td>
                <td>${row.movie.filename}</td>
                <td>${text.states[row.status]}</td>
                <td>${successBtn}</td>
                <td>${errorBtn}</td>
                <td>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Small button group">
                        <button type="button" class="btn btn-outline-secondary">
                            <span class="align-middle material-symbols-outlined">refresh</span>
                        </button>
                        <button type="button" class="btn btn-outline-danger">
                            <span class="align-middle material-symbols-outlined">delete</span>
                        </button>
                    </div>
                </td>
            </tr>`);
        })
    }

    function setView(j) {
        $("#viewLabel").html(j.title ?? '');
        $("#viewFilename").val(j.filename ?? '');
        $("#viewFileFolder").val(j.folders_id ?? 0);
        $("#viewPath").val(j.subfolder ?? '');
        $("#viewDataset").html("");

        let dataset = JSON.parse(j.meta.dataset);

        $.each(dataset, function (key, value) {
            if (key == 'path') {
                return;
            }
            if (key == 'movie') {
                return;
            }
            if (key == 'new_name') {
                return;
            }

            $("#viewDataset").append(`
                <tr>
                    <td>${key}</td>
                    <td>${typeof value === 'object' ? value.length : value}</td>
                </tr>
            `);
        });
        // Buttons
        $("#renameMovie").data("id", j.id);
        $("#moveMovie").data("id", j.id);
    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    let logs_skip = 0;
    let logs_take = 50;
    $.ajax({url: "/jobs?skip=" + logs_skip + "&take=" + logs_take})
        .always(function (j, t, r) {
            if (r.status == 200) {
                loadLogTable("#logTable", j);
            } else {
                error("Logs failed")
            }
        });


    $("#loadMoreLogs").click(function (e) {
        logs_skip = logs_take
        logs_take = logs_take + 50;
        $.ajax({url: "/jobs?skip=" + logs_skip + "&take=" + logs_take})
            .always(function (j, t, r) {
                if (r.status == 200) {
                    loadLogTable("#logTable", j);
                    success("");
                } else {
                    error("")
                }
            });
    });
    $("#logTable").on("click", ".showLog", function (e) {
        let json = $(this).data("logoutput");
        let rResponse = jQuery.parseJSON(decodeURIComponent(json)) ?? false;
        $("#logOutput").html('<pre>' + JSON.stringify(rResponse, null, 2) + '</pre>');
    });
    $(".view").click(function (e) {
        $.ajax({url: "/" + $(this).data("id")})
            .always(function (j, t, r) {
                if (r.status == 200) {
                    setView(j)
                }
            });
    });
    $(".deletePath").click(function (e) {
        $.ajax({url: "/path/delete/" + $(this).data("id")})
            .always(function (j, t, r) {
                if (r.status == 200) {
                    window.location.reload()
                } else {
                    error("")
                }
            });
    });
    $(".deactivatePath").click(function (e) {
        $.ajax({url: "/path/deactivate/" + $(this).data("id")})
            .always(function (j, t, r) {
                if (r.status == 200) {
                    window.location.reload()
                } else {
                    error("")
                }
            });
    });
    $(".activatePath").click(function (e) {
        $.ajax({url: "/path/activate/" + $(this).data("id")})
            .always(function (j, t, r) {
                if (r.status == 200) {
                    window.location.reload()
                } else {
                    error("")
                }
            });
    });
    $(".settingCheckbox").click(function (e) {
        $.ajax({method: "PUT", url: "/setting/" + $(this).data("key"), data: {value: ($(this).prop("checked"))}})
            .always(function (j, t, r) {
                if (r.status == 200) {
                    success("")
                } else {
                    error("")
                }
            });
    });
    $(".settingInput").blur(function (e) {
        $.ajax({method: "PUT", url: "/setting/" + $(this).data("key"), data: {value: ($(this).val())}})
            .always(function (j, t, r) {
                if (r.status == 200) {
                    success("")
                } else {
                    error("")
                }
            });
    });
    $("#createPath").click(function (e) {
        $.ajax({
            method: "POST",
            url: "/path",
            data: {path: $("#newPath").val(), type: $("#newPathType").val(), plex_key: $("#newPlexKey").val()}
        })
            .done(function () {
                window.location.reload()
            });
    });
    $("#pauseJobs").click(function (e) {
        $.ajax({method: "GET", url: "/jobs/pause"})
            .done(function () {
                window.location.reload()
            });
    });
    $("#resumeJobs").click(function (e) {
        $.ajax({method: "GET", url: "/jobs/resume"})
            .done(function () {
                window.location.reload()
            });
    });
    $("#renameMovie").click(function (e) {
        $.ajax({
            method: "PUT",
            url: "/" + $(this).data("id") + "/rename",
            data: {newfilename: $("#viewFilename").val()}
        })
            .done(function (j, t, r) {
                if (r.status == 200) {
                    success("")
                } else {
                    error("")
                }
            });
    });
    $("#moveMovie").click(function (e) {
        $.ajax({
            method: "PUT",
            url: "/" + $(this).data("id") + "/move",
            data: {folders_id: $("#viewFileFolder").val(), subfolder: $("#viewPath").val()}
        })
            .done(function (j, t, r) {
                if (r.status == 200) {
                    success("")
                } else {
                    error("")
                }
            });
    });
});
