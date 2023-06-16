function getQueryVariable(variable) {
    var query = window.location.search.substring(1);
    var vars = query.split("&");
    for (var i = 0; i < vars.length; i++) {
        var pair = vars[i].split("=");

        if (pair[0] == variable) {
            return pair[1];
        }
    }
    return false;
}

function updateQueryStringParameter(uri, key, value) {
    let currentURL = new URL(window.location.href);
    let params = currentURL.searchParams;
    if (params.has(key)) {
        params.delete(key);
    }
    params.append(key, value);
    window.history.pushState("object or string", "Title", currentURL.href);
}

function updateFilterInputs() {
    $(".filterSearch").each(function() {
        if (getQueryVariable("filter") != false) {
            if (this.id == getQueryVariable("filter")+"-filter") {
                if ($(this).css("display") == "inline-block" && $(this).css("visibility") == "visible" && getQueryVariable("value") == false) {
                    updateQueryStringParameter(window.location.href, "filter", "");
                    updateQueryStringParameter(window.location.href, "value", "");
                    updateFilterInputs();
                    return;
                }
                $(this).css("display", "inline-block");
                $(this).css("visibility", "visible");
            } else {
                $(this).css("display", "inline-block");
                $(this).css("visibility", "hidden");
            }
        } else {
            $(this).css("display", "none");
        }

        if (getQueryVariable("value") != false) {
            $(this).val(getQueryVariable("value"));
        } else {
            $(this).val("");
        }

        $(this).on("keyup", function(e) {
            updateQueryStringParameter(window.location.href, "value", $(this).val());
            filterRows();
        })        
    });
}

updateFilterInputs();

var filters = document.querySelectorAll("span.filter");
filters.forEach(function(filter) {
    filter.addEventListener("click", function(e) {
        e.preventDefault();
        e.stopPropagation();
        updateQueryStringParameter(window.location.href, "filter", e.target.parentElement.id);
        updateFilterInputs();
    });
});

function filterRows() {
    var filter = getQueryVariable("filter");
    var filterValue = getQueryVariable("value");

    if (filter) {
        var table = document.querySelector("table.filterable");
        var rows = Array.from(table.querySelectorAll("tr:nth-child(n+2)"));
        var column = table.querySelector("tr.key").querySelector("td#" + filter).cellIndex;
        rows.forEach(function(row) {
            if (row.cells[column].textContent.trim().toLowerCase().indexOf(filterValue.toLowerCase()) == -1) {
                row.style.display = "none";
            } else {
                row.style.display = "";
            }
        }
        );
    }
}