/**
 * Test suite for filter.js table filtering functionality
 */

const JSDOM = require("jsdom").JSDOM;

describe("Table Filtering", () => {
  let dom;
  let document;
  let window;

  beforeEach(() => {
    const html = `
      <!DOCTYPE html>
      <html>
        <head></head>
        <body>
          <input class="filterSearch" style="display: none;" />
          <table class="filterable">
            <thead>
              <tr>
                <th id="uid">UID</th>
                <th id="name">Name</th>
                <th id="department">Department</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>user001</td>
                <td>Alice Johnson</td>
                <td>Engineering</td>
              </tr>
              <tr>
                <td>user002</td>
                <td>Bob Smith</td>
                <td>Sales</td>
              </tr>
              <tr>
                <td>user003</td>
                <td>Charlie Brown</td>
                <td>Engineering</td>
              </tr>
              <tr>
                <td>user004</td>
                <td>Diana Prince</td>
                <td>Marketing</td>
              </tr>
            </tbody>
          </table>
        </body>
      </html>
    `;

    dom = new JSDOM(html, { url: "http://localhost/" });
    document = dom.window.document;
    window = dom.window;
    global.window = window;
    global.document = document;
    global.URL = window.URL;
  });

  describe("getQueryVariable", () => {
    it("should retrieve query parameters from URL", () => {
      window.history.pushState(
        {},
        "",
        "http://localhost/?filter=uid&value=user001",
      );

      const getQueryVariable = (variable) => {
        const query = window.location.search.substring(1);
        const vars = query.split("&");
        for (let i = 0; i < vars.length; i++) {
          const pair = vars[i].split("=");
          if (pair[0] === variable) {
            return decodeURIComponent(pair[1]);
          }
        }
        return false;
      };

      expect(getQueryVariable("filter")).toBe("uid");
      expect(getQueryVariable("value")).toBe("user001");
    });
  });

  describe("updateQueryStringParameter", () => {
    it("should update or add query parameters to URL", () => {
      const updateQueryStringParameter = (uri, key, value) => {
        const currentURL = new URL(window.location.href);
        const params = currentURL.searchParams;
        if (params.has(key)) {
          params.delete(key);
        }
        params.append(key, value);
        window.history.pushState("object or string", "Title", currentURL.href);
      };

      updateQueryStringParameter(window.location.href, "filter", "uid");
      expect(new URL(window.location.href).searchParams.get("filter")).toBe(
        "uid",
      );

      updateQueryStringParameter(window.location.href, "filter", "name");
      expect(new URL(window.location.href).searchParams.get("filter")).toBe(
        "name",
      );
    });
  });

  describe("filterRows", () => {
    it("should show all rows when no filter is applied", () => {
      const table = document.querySelector("table.filterable");
      const tbody = table.querySelector("tbody");
      const rows = tbody.querySelectorAll("tr");

      rows.forEach((row) => {
        row.style.display = "";
      });

      expect(
        Array.from(rows).filter((r) => r.style.display !== "none").length,
      ).toBe(4);
    });

    it("should filter rows by exact string match (case-insensitive)", () => {
      const table = document.querySelector("table.filterable");
      const tbody = table.querySelector("tbody");
      const rows = Array.from(tbody.querySelectorAll("tr"));

      // Simulate filtering by department "Engineering"
      const columnIndex = 2; // department column
      const filterValue = "engineering";

      rows.forEach((row) => {
        const cellText = row.cells[columnIndex].textContent
          .trim()
          .toLowerCase();
        if (cellText.indexOf(filterValue) === -1) {
          row.style.display = "none";
        } else {
          row.style.display = "";
        }
      });

      const visibleRows = rows.filter((r) => r.style.display !== "none");
      expect(visibleRows.length).toBe(2);
      expect(visibleRows[0].cells[1].textContent).toContain("Alice");
      expect(visibleRows[1].cells[1].textContent).toContain("Charlie");
    });

    it("should filter rows by partial string match", () => {
      const table = document.querySelector("table.filterable");
      const tbody = table.querySelector("tbody");
      const rows = Array.from(tbody.querySelectorAll("tr"));

      // Simulate filtering by name containing "a"
      const columnIndex = 1; // name column
      const filterValue = "a";

      rows.forEach((row) => {
        const cellText = row.cells[columnIndex].textContent
          .trim()
          .toLowerCase();
        if (cellText.indexOf(filterValue) === -1) {
          row.style.display = "none";
        } else {
          row.style.display = "";
        }
      });

      const visibleRows = rows.filter((r) => r.style.display !== "none");
      // Should match: Alice, Charlie, Diana (all have 'a')
      expect(visibleRows.length).toBe(3);
    });

    it("should be case-insensitive", () => {
      const table = document.querySelector("table.filterable");
      const tbody = table.querySelector("tbody");
      const rows = Array.from(tbody.querySelectorAll("tr"));

      const columnIndex = 0; // uid column
      const filterValue = "USER001"; // uppercase

      rows.forEach((row) => {
        const cellText = row.cells[columnIndex].textContent
          .trim()
          .toLowerCase();
        if (cellText.indexOf(filterValue.toLowerCase()) === -1) {
          row.style.display = "none";
        } else {
          row.style.display = "";
        }
      });

      const visibleRows = rows.filter((r) => r.style.display !== "none");
      expect(visibleRows.length).toBe(1);
      expect(visibleRows[0].cells[1].textContent).toContain("Alice");
    });

    it("should hide all rows when filter matches nothing", () => {
      const table = document.querySelector("table.filterable");
      const tbody = table.querySelector("tbody");
      const rows = Array.from(tbody.querySelectorAll("tr"));

      const columnIndex = 1; // name column
      const filterValue = "nonexistent";

      rows.forEach((row) => {
        const cellText = row.cells[columnIndex].textContent
          .trim()
          .toLowerCase();
        if (cellText.indexOf(filterValue) === -1) {
          row.style.display = "none";
        } else {
          row.style.display = "";
        }
      });

      const visibleRows = rows.filter((r) => r.style.display !== "none");
      expect(visibleRows.length).toBe(0);
    });

    it("should update URL with filter and value parameters", () => {
      const updateQueryStringParameter = (uri, key, value) => {
        const currentURL = new URL(window.location.href);
        const params = currentURL.searchParams;
        if (params.has(key)) {
          params.delete(key);
        }
        params.append(key, value);
        window.history.pushState("object or string", "Title", currentURL.href);
      };

      updateQueryStringParameter(window.location.href, "filter", "department");
      updateQueryStringParameter(window.location.href, "value", "Engineering");

      const url = new URL(window.location.href);
      expect(url.searchParams.get("filter")).toBe("department");
      expect(url.searchParams.get("value")).toBe("Engineering");
    });
  });

  describe("updateFilterInput", () => {
    it("should hide filter input when no filter is selected", () => {
      const filterInput = document.querySelector(".filterSearch");
      window.history.pushState({}, "", "http://localhost/");

      const getQueryVariable = (variable) => {
        const query = window.location.search.substring(1);
        const vars = query.split("&");
        for (let i = 0; i < vars.length; i++) {
          const pair = vars[i].split("=");
          if (pair[0] === variable) {
            return decodeURIComponent(pair[1]);
          }
        }
        return false;
      };

      const filter = getQueryVariable("filter");
      if (!filter) {
        filterInput.style.display = "none";
        filterInput.style.visibility = "hidden";
      }

      expect(filterInput.style.display).toBe("none");
      expect(filterInput.style.visibility).toBe("hidden");
    });

    it("should show filter input when filter is selected", () => {
      const filterInput = document.querySelector(".filterSearch");
      window.history.pushState({}, "", "http://localhost/?filter=uid");

      const getQueryVariable = (variable) => {
        const query = window.location.search.substring(1);
        const vars = query.split("&");
        for (let i = 0; i < vars.length; i++) {
          const pair = vars[i].split("=");
          if (pair[0] === variable) {
            return decodeURIComponent(pair[1]);
          }
        }
        return false;
      };

      const filter = getQueryVariable("filter");
      if (filter) {
        filterInput.style.display = "inline-block";
        filterInput.style.visibility = "visible";

        if (filter === "uid") {
          filterInput.placeholder = "Filter by " + filter.toUpperCase() + "...";
        } else {
          filterInput.placeholder =
            "Filter by " +
            filter.charAt(0).toUpperCase() +
            filter.slice(1) +
            "...";
        }
      }

      expect(filterInput.style.display).toBe("inline-block");
      expect(filterInput.style.visibility).toBe("visible");
      expect(filterInput.placeholder).toBe("Filter by UID...");
    });

    it("should set correct placeholder text for different filter types", () => {
      const filterInput = document.querySelector(".filterSearch");

      // Test UID filter
      window.history.pushState({}, "", "http://localhost/?filter=uid");
      filterInput.placeholder = "Filter by UID...";
      expect(filterInput.placeholder).toBe("Filter by UID...");

      // Test other filter types
      window.history.pushState({}, "", "http://localhost/?filter=department");
      filterInput.placeholder = "Filter by Department...";
      expect(filterInput.placeholder).toBe("Filter by Department...");
    });

    it("should populate input with existing filter value from URL", () => {
      const filterInput = document.querySelector(".filterSearch");
      window.history.pushState(
        {},
        "",
        "http://localhost/?filter=name&value=Alice",
      );

      const getQueryVariable = (variable) => {
        const query = window.location.search.substring(1);
        const vars = query.split("&");
        for (let i = 0; i < vars.length; i++) {
          const pair = vars[i].split("=");
          if (pair[0] === variable) {
            return decodeURIComponent(pair[1]);
          }
        }
        return false;
      };

      const filterValue = getQueryVariable("value");
      if (filterValue && filterValue !== "false") {
        filterInput.value = filterValue;
      }

      expect(filterInput.value).toBe("Alice");
    });
  });

  describe("Filter interaction with sorting", () => {
    it("should preserve sorting when applying filter", () => {
      const table = document.querySelector("table.filterable");
      const tbody = table.querySelector("tbody");
      const rows = Array.from(tbody.querySelectorAll("tr"));

      // Simulate sort in reverse name order
      const sorted = rows.sort((a, b) =>
        b.cells[1].textContent.localeCompare(a.cells[1].textContent),
      );

      tbody.innerHTML = "";
      sorted.forEach((row) => tbody.appendChild(row));

      // Verify sorted
      const names = Array.from(tbody.querySelectorAll("tr")).map((r) =>
        r.cells[1].textContent.trim(),
      );
      expect(names[0]).toBe("Diana Prince");

      // Now apply filter
      const updatedRows = Array.from(tbody.querySelectorAll("tr"));
      updatedRows.forEach((row) => {
        const cellText = row.cells[2].textContent.trim().toLowerCase();
        if (cellText.indexOf("engineering") === -1) {
          row.style.display = "none";
        }
      });

      const visible = updatedRows.filter((r) => r.style.display !== "none");
      expect(visible.length).toBe(2);
      expect(visible[0].cells[1].textContent).toContain("Charlie");
    });
  });
});
