(function () {
  var table = document.querySelector("table.sortable");
  if (!table) {
    return;
  }
  table.querySelectorAll("th").forEach(function (th) {
    th.addEventListener("click", function (e) {
      if (th.innerHTML !== "Actions") {
        if (e.target.classList.contains("filter")) {
          updateQueryStringParameter(
            window.location.href,
            "filter",
            e.target.parentElement.id,
          );
          updateFilterInput();
        } else {
          var column = th.cellIndex;
          var tbody = table.querySelector("tbody");
          var rows = Array.from(tbody.querySelectorAll(":scope > tr:nth-child(n+2)"));
          var order = th.classList.toggle("asc") ? 1 : -1;
          rows.sort(function (a, b) {
            return (
              order *
              a.cells[column].textContent
                .trim()
                .localeCompare(b.cells[column].textContent.trim(), undefined, {
                  numeric: true,
                })
            );
          });
          rows.forEach(function (row) {
            tbody.appendChild(row);
          });
          table.querySelectorAll("th").forEach(function (header) {
            header.innerHTML = header.innerHTML.replace(/ ▲| ▼/, "");
          });
          var orderSymbol = order === 1 ? "&#x25B2;" : "&#x25BC;";
          th.innerHTML = th.innerHTML + " " + orderSymbol;
          updateQueryStringParameter(window.location.href, "sort", th.id);
          updateQueryStringParameter(
            window.location.href,
            "order",
            order === 1 ? "asc" : "desc",
          );
        }
      }
    });
  });
})();

function getQueryVariable(variable) {
  var query = window.location.search.substring(1);
  var vars = query.split("&");
  for (var i = 0; i < vars.length; i++) {
    var pair = vars[i].split("=");

    if (pair[0] === variable) {
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

window.onload = function () {
  var sort = getQueryVariable("sort");
  var order = getQueryVariable("order");
  if (sort) {
    var sortElement = document.getElementById(sort);
    if (sortElement) {
      if (order === "asc") {
        sortElement.click();
      } else if (order === "desc") {
        sortElement.click();
        sortElement.click();
      }
    }
  }
  filterRows();
};
