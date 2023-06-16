var table = document.querySelector("table.sortable");
table.querySelectorAll("td").forEach(function(td) {
    td.addEventListener("click", function(e) {
        if (td.parentElement.classList.contains("key") && td.innerHTML != "Actions") {
            if (e.target.classList.contains("filter")) {
                updateQueryStringParameter(window.location.href, "filter", e.target.parentElement.id);
                updateFilterInputs();
            } else {
                var column = td.cellIndex;
                var rows = Array.from(table.querySelectorAll("tr:nth-child(n+2)"));
                var order = td.classList.toggle("asc") ? 1 : -1;
                rows.sort(function(a, b) {
                    return order * (a.cells[column].textContent.trim().localeCompare(b.cells[column].textContent.trim(), undefined, {
                        numeric: true
                    }));
                });
                rows.forEach(function(row) {
                    table.appendChild(row);
                });
                var keys = document.querySelectorAll("tr.key");
                keys.forEach(function(key) {
                    key.querySelectorAll("td").forEach(function(td) {
                        td.innerHTML = td.innerHTML.replace(/ ▲| ▼/, "");
                    });
                });
                var orderSymbol = order == 1 ? "&#x25B2;" : "&#x25BC;";
                td.innerHTML = td.innerHTML + " " + orderSymbol;
                updateQueryStringParameter(window.location.href, "sort", td.id);
                updateQueryStringParameter(window.location.href, "order", order == 1 ? "asc" : "desc");
            }
        }
    });
});

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

window.onload = function() {
    var sort = getQueryVariable("sort");
    var order = getQueryVariable("order");
    if (sort) {
        var sortElement = document.getElementById(sort);
        if (sortElement) {
            if (order == "asc") {
                sortElement.click();   
            } else if (order == "desc") {
                sortElement.click();
                sortElement.click();
            }
        }
    }
    filterRows();
}