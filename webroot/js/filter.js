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

function updateFilterInput() {
    const commonFilterInputBox = document.querySelector(".filterSearch");
    commonFilterInputBox.style.display = "none";
    commonFilterInputBox.style.visibility = "hidden";
    commonFilterInputBox.value = "";

    var filter = getQueryVariable("filter");
    if (filter) {
        commonFilterInputBox.style.display = "inline-block";
        commonFilterInputBox.style.visibility = "visible";

        if (filter == "uid") {
            commonFilterInputBox.placeholder = "Filter by " + filter.toUpperCase() + '...';
        } else {
            commonFilterInputBox.placeholder = "Filter by " + filter.charAt(0).toUpperCase() + filter.slice(1) + '...';
        }

        if (getQueryVariable("value") != false) {
            commonFilterInputBox.value = getQueryVariable("value");
            filterRows();
        }

        commonFilterInputBox.addEventListener("keyup", function(e) {
            updateQueryStringParameter(window.location.href, "value", e.target.value);
            filterRows();
        });
    }
}

updateFilterInput();

var filters = document.querySelectorAll("span.filter");
filters.forEach(function(filter) {
    filter.addEventListener("click", function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (e.target.parentElement.id != getQueryVariable("filter")) {
            updateQueryStringParameter(window.location.href, "filter", e.target.parentElement.id);
            updateQueryStringParameter(window.location.href, "value", "");
            filterRows();
        } else {
            updateQueryStringParameter(window.location.href, "filter", "");
            updateQueryStringParameter(window.location.href, "value", "");
            filterRows();
        }
        updateFilterInput();
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
