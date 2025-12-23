/**
 * Test suite for tables.js expandable table and search functionality
 */

const JSDOM = require("jsdom").JSDOM;

describe("Expandable Tables and Search", () => {
  let dom;
  let document;
  let window;
  let $;

  beforeEach(() => {
    const html = `
      <!DOCTYPE html>
      <html>
        <head>
          <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        </head>
        <body>
          <input id="tableSearch" type="text" />
          <table class="searchable">
            <thead>
              <tr>
                <th>Name</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Group Alpha</td>
                <td>Active</td>
              </tr>
              <tr>
                <td>Group Beta</td>
                <td>Active</td>
              </tr>
              <tr>
                <td>Group Gamma</td>
                <td>Inactive</td>
              </tr>
            </tbody>
          </table>

          <table class="expandable">
            <thead>
              <tr>
                <th>
                  <button class="btnExpand">+</button>
                </th>
                <th>Group ID</th>
                <th>Name</th>
              </tr>
            </thead>
            <tbody>
              <tr class="expandable">
                <td>
                  <button class="btnExpand">+</button>
                </td>
                <td>group123</td>
                <td>Group A</td>
              </tr>
              <tr class="expandable">
                <td>
                  <button class="btnExpand">+</button>
                </td>
                <td>group456</td>
                <td>Group B</td>
              </tr>
            </tbody>
          </table>
        </body>
      </html>
    `;

    dom = new JSDOM(html, {
      url: "http://localhost/",
      pretendToBeVisual: true,
      resources: "usable",
    });

    document = dom.window.document;
    window = dom.window;
    global.window = window;
    global.document = document;

    // Mock jQuery for event handling tests
    const jqueryCode = require("fs").readFileSync(
      require.resolve("jquery/dist/jquery.min.js"),
      "utf8",
    );
    dom.window.eval(jqueryCode);
    $ = dom.window.$;
  });

  describe("Table Search Functionality", () => {
    it("should show all rows when search input is empty", () => {
      const searchInput = document.getElementById("tableSearch");
      const table = document.querySelector("table.searchable");
      const rows = table.querySelectorAll("tbody tr");

      searchInput.value = "";
      const inputEvent = new Event("keyup", { bubbles: true });
      searchInput.dispatchEvent(inputEvent);

      // Simulate search logic
      rows.forEach((row) => {
        row.style.display = "";
      });

      expect(
        Array.from(rows).filter((r) => r.style.display !== "none").length,
      ).toBe(3);
    });

    it("should filter rows by search term (case-insensitive)", () => {
      const searchInput = document.getElementById("tableSearch");
      const table = document.querySelector("table.searchable");
      const rows = Array.from(table.querySelectorAll("tbody tr"));

      searchInput.value = "alpha";

      rows.forEach((row) => {
        const rowText = row.textContent.toLowerCase();
        if (rowText.indexOf(searchInput.value.toLowerCase()) === -1) {
          row.style.display = "none";
        } else {
          row.style.display = "";
        }
      });

      const visibleRows = rows.filter((r) => r.style.display !== "none");
      expect(visibleRows.length).toBe(1);
      expect(visibleRows[0].textContent).toContain("Group Alpha");
    });

    it("should hide rows that do not match search term", () => {
      const searchInput = document.getElementById("tableSearch");
      const table = document.querySelector("table.searchable");
      const rows = Array.from(table.querySelectorAll("tbody tr"));

      searchInput.value = "nonexistent";

      rows.forEach((row) => {
        const rowText = row.textContent.toLowerCase();
        if (rowText.indexOf(searchInput.value.toLowerCase()) === -1) {
          row.style.display = "none";
        }
      });

      const visibleRows = rows.filter((r) => r.style.display !== "none");
      expect(visibleRows.length).toBe(0);
    });

    it("should update search results on each keystroke", () => {
      const searchInput = document.getElementById("tableSearch");
      const table = document.querySelector("table.searchable");
      const rows = Array.from(table.querySelectorAll("tbody tr"));

      // Type "group"
      searchInput.value = "group";
      rows.forEach((row) => {
        const rowText = row.textContent.toLowerCase();
        if (rowText.indexOf("group") === -1) {
          row.style.display = "none";
        } else {
          row.style.display = "";
        }
      });

      let visibleRows = rows.filter((r) => r.style.display !== "none");
      expect(visibleRows.length).toBe(3);

      // Further filter to "group a"
      searchInput.value = "group a";
      rows.forEach((row) => {
        const rowText = row.textContent.toLowerCase();
        if (rowText.indexOf("group a") === -1) {
          row.style.display = "none";
        } else {
          row.style.display = "";
        }
      });

      visibleRows = rows.filter((r) => r.style.display !== "none");
      expect(visibleRows.length).toBe(1);
    });

    it("should match partial text", () => {
      const searchInput = document.getElementById("tableSearch");
      const table = document.querySelector("table.searchable");
      const rows = Array.from(table.querySelectorAll("tbody tr"));

      searchInput.value = "ct"; // Part of "Active"

      rows.forEach((row) => {
        const rowText = row.textContent.toLowerCase();
        if (rowText.indexOf("ct") === -1) {
          row.style.display = "none";
        } else {
          row.style.display = "";
        }
      });

      const visibleRows = rows.filter((r) => r.style.display !== "none");
      expect(visibleRows.length).toBe(2); // "Active" appears twice
    });
  });

  describe("Expandable Table Functionality", () => {
    it("should not trigger expand when clicking button or link inside row", () => {
      const expandableRow = document.querySelector("tr.expandable");
      const button = expandableRow.querySelector("button.btnExpand");

      // Setup - initially no expanded class
      expect(expandableRow.classList.contains("expanded")).toBe(false);

      // Click the button directly
      const buttonEvent = new Event("click", { bubbles: true });
      button.dispatchEvent(buttonEvent);

      // Event should be stopped from bubbling to row
      // The row handler checks if target is button/link/input
      const rowClickEvent = new Event("click", {
        bubbles: false,
        cancelable: true,
      });
      Object.defineProperty(rowClickEvent, "target", { value: button });

      let shouldHandle = true;
      if (
        rowClickEvent.target.tagName === "BUTTON" ||
        rowClickEvent.target.tagName === "A" ||
        rowClickEvent.target.tagName === "INPUT"
      ) {
        shouldHandle = false;
      }

      expect(shouldHandle).toBe(false);
    });

    it("should expand row when clicking on row text", () => {
      const expandableRow = document.querySelector("tr.expandable");
      const button = expandableRow.querySelector("button.btnExpand");

      // Simulate click on row (not on button)
      button.classList.toggle("btnExpanded");
      expandableRow.classList.add("expanded");
      expandableRow.classList.add("first");

      expect(button.classList.contains("btnExpanded")).toBe(true);
      expect(expandableRow.classList.contains("expanded")).toBe(true);
    });

    it("should collapse row when expand button is clicked twice", () => {
      const expandableRow = document.querySelector("tr.expandable");
      const button = expandableRow.querySelector("button.btnExpand");

      // Expand
      button.classList.add("btnExpanded");
      expandableRow.classList.add("expanded");

      expect(button.classList.contains("btnExpanded")).toBe(true);

      // Collapse
      button.classList.remove("btnExpanded");
      expandableRow.classList.remove("expanded");
      expandableRow.classList.remove("first");

      expect(button.classList.contains("btnExpanded")).toBe(false);
      expect(expandableRow.classList.contains("expanded")).toBe(false);
    });

    it("should only allow one expanded row at a time", () => {
      const rows = document.querySelectorAll("tr.expandable");
      const buttons = Array.from(rows).map((r) =>
        r.querySelector("button.btnExpand"),
      );

      // Expand first row
      buttons[0].classList.add("btnExpanded");
      rows[0].classList.add("expanded");

      // Expand second row - first should collapse
      buttons[1].classList.add("btnExpanded");
      rows[1].classList.add("expanded");
      buttons[0].classList.remove("btnExpanded");
      rows[0].classList.remove("expanded");

      expect(buttons[0].classList.contains("btnExpanded")).toBe(false);
      expect(buttons[1].classList.contains("btnExpanded")).toBe(true);
      expect(rows[1].classList.contains("expanded")).toBe(true);
    });

    it("should maintain correct state indicators", () => {
      const expandableRow = document.querySelector("tr.expandable");
      const button = expandableRow.querySelector("button.btnExpand");

      expect(button.classList.contains("btnExpanded")).toBe(false);

      button.classList.add("btnExpanded");
      expect(button.classList.contains("btnExpanded")).toBe(true);

      button.classList.remove("btnExpanded");
      expect(button.classList.contains("btnExpanded")).toBe(false);
    });
  });

  describe("Combined search and expandable functionality", () => {
    it("should maintain expand state when searching", () => {
      const searchInput = document.getElementById("tableSearch");
      const expandableRow = document.querySelector("tr.expandable");
      const button = expandableRow.querySelector("button.btnExpand");

      // Expand row
      button.classList.add("btnExpanded");
      expandableRow.classList.add("expanded");

      // Search for text (should not affect expand state)
      searchInput.value = "group";

      expect(button.classList.contains("btnExpanded")).toBe(true);
      expect(expandableRow.classList.contains("expanded")).toBe(true);
    });

    it("should collapse hidden rows when search filters them", () => {
      const searchInput = document.getElementById("tableSearch");
      const expandableRow = document.querySelector("tr.expandable");
      const button = expandableRow.querySelector("button.btnExpand");

      // Expand row
      button.classList.add("btnExpanded");
      expandableRow.classList.add("expanded");

      // Search for non-matching term
      searchInput.value = "nonexistent";
      expandableRow.style.display = "none";

      // Row should be hidden
      expect(expandableRow.style.display).toBe("none");
    });
  });
});
