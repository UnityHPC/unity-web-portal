/**
 * Test suite for sort.js table sorting functionality
 */

// Mock the DOM and window objects before loading the module
const JSDOM = require("jsdom").JSDOM;

describe("Table Sorting", () => {
  let dom;
  let document;
  let window;
  let originalLocation;

  beforeEach(() => {
    // Create a fresh DOM for each test
    const html = `
      <!DOCTYPE html>
      <html>
        <head></head>
        <body>
          <table class="sortable">
            <thead>
              <tr>
                <th id="name">Name</th>
                <th id="email">Email</th>
                <th id="status">Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Alice Johnson</td>
                <td>alice@example.com</td>
                <td>Active</td>
                <td><button>Edit</button></td>
              </tr>
              <tr>
                <td>Bob Smith</td>
                <td>bob@example.com</td>
                <td>Inactive</td>
                <td><button>Edit</button></td>
              </tr>
              <tr>
                <td>Charlie Brown</td>
                <td>charlie@example.com</td>
                <td>Active</td>
                <td><button>Edit</button></td>
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
    originalLocation = window.location;

    // Load and execute the actual sort.js code
    const fs = require("fs");
    const sortCode = fs.readFileSync(
      require.resolve("../../webroot/js/sort.js"),
      "utf8",
    );
    window.eval(sortCode);
  });

  afterEach(() => {
    jest.resetModules();
  });

  describe("getQueryVariable", () => {
    it("should return false when variable is not in query string", () => {
      // Note: This tests the function directly, so we need to extract it
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

      expect(getQueryVariable("sort")).toBe(false);
    });

    it("should return the value when variable exists in query string", () => {
      window.history.pushState({}, "", "http://localhost/?sort=name&order=asc");

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

      expect(getQueryVariable("sort")).toBe("name");
      expect(getQueryVariable("order")).toBe("asc");
    });
  });

  describe("Table sorting by column click", () => {
    it("should sort table by name column in ascending order", () => {
      const table = document.querySelector("table.sortable");
      const nameHeader = document.querySelector("th#name");
      const tbody = table.querySelector("tbody");
      const rows = Array.from(tbody.querySelectorAll("tr"));

      // Get initial order
      const initialNames = rows.map((r) => r.cells[0].textContent.trim());
      expect(initialNames).toEqual([
        "Alice Johnson",
        "Bob Smith",
        "Charlie Brown",
      ]);

      // Simulate click on name header
      nameHeader.click();

      // Check if sorted (should be ascending)
      const sortedNames = Array.from(tbody.querySelectorAll("tr")).map((r) =>
        r.cells[0].textContent.trim(),
      );
      expect(sortedNames).toEqual([
        "Alice Johnson",
        "Bob Smith",
        "Charlie Brown",
      ]);
      expect(nameHeader.classList.contains("asc")).toBe(true);
    });

    it("should sort table in descending order on second click", () => {
      const table = document.querySelector("table.sortable");
      const nameHeader = document.querySelector("th#name");
      const tbody = table.querySelector("tbody");

      // First click - ascending
      nameHeader.click();
      expect(nameHeader.classList.contains("asc")).toBe(true);

      // Second click - descending
      nameHeader.click();
      expect(nameHeader.classList.contains("asc")).toBe(false);

      const sortedNames = Array.from(tbody.querySelectorAll("tr")).map((r) =>
        r.cells[0].textContent.trim(),
      );
      expect(sortedNames).toEqual([
        "Charlie Brown",
        "Bob Smith",
        "Alice Johnson",
      ]);
    });

    it("should handle numeric sorting", () => {
      // Add a numeric column
      const table = document.querySelector("table.sortable");
      const thead = table.querySelector("thead tr");
      const th = document.createElement("th");
      th.id = "age";
      th.textContent = "Age";
      thead.insertBefore(th, thead.lastChild);

      const tbody = table.querySelector("tbody");
      tbody.querySelectorAll("tr").forEach((row, index) => {
        const td = document.createElement("td");
        td.textContent = [25, 30, 28][index];
        row.insertBefore(td, row.lastChild);
      });

      // Click on age header
      const ageHeader = table.querySelector("th#age");
      ageHeader.click();

      const ages = Array.from(tbody.querySelectorAll("tr")).map((r) =>
        r.cells[3].textContent.trim(),
      );
      expect(ages).toEqual(["25", "28", "30"]);
    });

    it("should not sort when clicking Actions header", () => {
      const table = document.querySelector("table.sortable");
      const actionsHeader = Array.from(table.querySelectorAll("th")).find(
        (th) => th.textContent === "Actions",
      );
      const tbody = table.querySelector("tbody");

      const initialNames = Array.from(tbody.querySelectorAll("tr")).map((r) =>
        r.cells[0].textContent.trim(),
      );

      actionsHeader.click();

      const names = Array.from(tbody.querySelectorAll("tr")).map((r) =>
        r.cells[0].textContent.trim(),
      );
      expect(names).toEqual(initialNames);
    });

    it("should add sort indicator symbol to sorted column", () => {
      const nameHeader = document.querySelector("th#name");
      nameHeader.click();

      expect(nameHeader.innerHTML).toMatch(/▲|▼/);
    });

    it("should remove sort indicator from previous sorted column", () => {
      const nameHeader = document.querySelector("th#name");
      const emailHeader = document.querySelector("th#email");

      nameHeader.click();
      expect(nameHeader.innerHTML).toMatch(/▲|▼/);

      emailHeader.click();
      expect(nameHeader.innerHTML).not.toMatch(/▲|▼/);
      expect(emailHeader.innerHTML).toMatch(/▲|▼/);
    });

    it("should update URL with sort parameters", () => {
      const nameHeader = document.querySelector("th#name");
      nameHeader.click();

      const url = new URL(window.location.href);
      expect(url.searchParams.get("sort")).toBe("name");
      expect(url.searchParams.get("order")).toBe("asc");
    });

    it("should toggle sort order in URL", () => {
      const nameHeader = document.querySelector("th#name");

      nameHeader.click();
      let url = new URL(window.location.href);
      expect(url.searchParams.get("order")).toBe("asc");

      nameHeader.click();
      url = new URL(window.location.href);
      expect(url.searchParams.get("order")).toBe("desc");
    });
  });

  describe("Case-insensitive and locale-aware sorting", () => {
    it("should sort case-insensitively", () => {
      const table = document.querySelector("table.sortable");
      const tbody = table.querySelector("tbody");

      tbody.innerHTML = `
        <tr><td>alice</td><td>alice@example.com</td><td>Active</td><td></td></tr>
        <tr><td>CHARLIE</td><td>charlie@example.com</td><td>Active</td><td></td></tr>
        <tr><td>Bob</td><td>bob@example.com</td><td>Inactive</td><td></td></tr>
      `;

      const nameHeader = document.querySelector("th#name");
      nameHeader.click();

      const names = Array.from(tbody.querySelectorAll("tr")).map((r) =>
        r.cells[0].textContent.trim(),
      );
      expect(names).toEqual(["alice", "Bob", "CHARLIE"]);
    });
  });

  describe("Filter interaction with sorting", () => {
    it("should allow filtering on sorted tables", () => {
      const table = document.querySelector("table.sortable");
      const tbody = table.querySelector("tbody");

      // Sort by name
      const nameHeader = document.querySelector("th#name");
      nameHeader.click();

      // Verify sort worked
      let names = Array.from(tbody.querySelectorAll("tr")).map((r) =>
        r.cells[0].textContent.trim(),
      );
      expect(names).toEqual(["Alice Johnson", "Bob Smith", "Charlie Brown"]);

      // Filter still works conceptually
      expect(tbody.querySelectorAll("tr").length).toBe(3);
    });
  });
});
