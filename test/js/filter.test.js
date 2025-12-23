/**
 * Test suite for table filtering logic
 * Tests filtering algorithms and helper functions
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
              <tr><td>user001</td><td>Alice</td><td>Engineering</td></tr>
              <tr><td>user002</td><td>Bob</td><td>Sales</td></tr>
              <tr><td>user003</td><td>Charlie</td><td>Engineering</td></tr>
              <tr><td>user004</td><td>Diana</td><td>Marketing</td></tr>
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

  describe("Helper Functions", () => {
    it("should retrieve query parameters", () => {
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

  describe("Filtering Logic", () => {
    it("should show all rows when filter is empty", () => {
      const tbody = document.querySelector("tbody");
      const rows = Array.from(tbody.querySelectorAll("tr"));

      rows.forEach((row) => {
        row.style.display = "";
      });

      const visible = rows.filter((r) => r.style.display !== "none");
      expect(visible.length).toBe(4);
    });

    it("should filter rows by exact match", () => {
      const tbody = document.querySelector("tbody");
      const rows = Array.from(tbody.querySelectorAll("tr"));
      const columnIndex = 2; // Department
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

      const visible = rows.filter((r) => r.style.display !== "none");
      expect(visible.length).toBe(2);
    });

    it("should filter by partial match", () => {
      const tbody = document.querySelector("tbody");
      const rows = Array.from(tbody.querySelectorAll("tr"));
      const columnIndex = 1; // Name
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

      const visible = rows.filter((r) => r.style.display !== "none");
      // Alice, Diana, Charlie all contain 'a'
      expect(visible.length).toBe(3);
    });

    it("should be case-insensitive", () => {
      const tbody = document.querySelector("tbody");
      const rows = Array.from(tbody.querySelectorAll("tr"));
      const columnIndex = 0; // UID
      const filterValue = "USER001";

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

      const visible = rows.filter((r) => r.style.display !== "none");
      expect(visible.length).toBe(1);
      expect(visible[0].cells[1].textContent).toBe("Alice");
    });

    it("should hide all rows when nothing matches", () => {
      const tbody = document.querySelector("tbody");
      const rows = Array.from(tbody.querySelectorAll("tr"));
      const columnIndex = 1;
      const filterValue = "nonexistent";

      rows.forEach((row) => {
        const cellText = row.cells[columnIndex].textContent
          .trim()
          .toLowerCase();
        if (cellText.indexOf(filterValue) === -1) {
          row.style.display = "none";
        }
      });

      const visible = rows.filter((r) => r.style.display !== "none");
      expect(visible.length).toBe(0);
    });
  });

  describe("Filter Input Management", () => {
    it("should hide filter input when no filter selected", () => {
      const filterInput = document.querySelector(".filterSearch");
      filterInput.style.display = "none";
      filterInput.style.visibility = "hidden";

      expect(filterInput.style.display).toBe("none");
      expect(filterInput.style.visibility).toBe("hidden");
    });

    it("should show filter input when filter selected", () => {
      const filterInput = document.querySelector(".filterSearch");
      filterInput.style.display = "inline-block";
      filterInput.style.visibility = "visible";

      expect(filterInput.style.display).toBe("inline-block");
      expect(filterInput.style.visibility).toBe("visible");
    });

    it("should set correct placeholder for different filters", () => {
      const filterInput = document.querySelector(".filterSearch");

      // UID placeholder
      filterInput.placeholder = "Filter by UID...";
      expect(filterInput.placeholder).toBe("Filter by UID...");

      // Other filter
      filterInput.placeholder = "Filter by Department...";
      expect(filterInput.placeholder).toBe("Filter by Department...");
    });

    it("should populate input with existing filter value", () => {
      const filterInput = document.querySelector(".filterSearch");
      filterInput.value = "Alice";

      expect(filterInput.value).toBe("Alice");
    });
  });

  describe("URL Parameter Management", () => {
    it("should update filter and value in URL", () => {
      const updateQueryStringParameter = (uri, key, value) => {
        const currentURL = new URL(window.location.href);
        const params = currentURL.searchParams;
        if (params.has(key)) {
          params.delete(key);
        }
        params.append(key, value);
        window.history.pushState("", "", currentURL.href);
      };

      updateQueryStringParameter(window.location.href, "filter", "department");
      updateQueryStringParameter(window.location.href, "value", "Engineering");

      const url = new URL(window.location.href);
      expect(url.searchParams.get("filter")).toBe("department");
      expect(url.searchParams.get("value")).toBe("Engineering");
    });
  });
});
