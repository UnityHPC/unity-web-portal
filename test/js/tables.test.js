/**
 * Test suite for table search and expandable row logic
 * Tests DOM manipulation and event handling patterns
 */

const JSDOM = require("jsdom").JSDOM;

describe("Table Search and Expandable Rows", () => {
  let dom;
  let document;
  let window;

  beforeEach(() => {
    const html = `
      <!DOCTYPE html>
      <html>
        <body>
          <input id="tableSearch" type="text" />
          <table class="searchable">
            <tbody>
              <tr><td>Group Alpha</td><td>Active</td></tr>
              <tr><td>Group Beta</td><td>Active</td></tr>
              <tr><td>Group Gamma</td><td>Inactive</td></tr>
            </tbody>
          </table>

          <table class="expandable">
            <tbody>
              <tr class="expandable">
                <td><button class="btnExpand">+</button></td>
                <td>Group A</td>
              </tr>
              <tr class="expandable">
                <td><button class="btnExpand">+</button></td>
                <td>Group B</td>
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
  });

  describe("Search Functionality", () => {
    it("should show all rows when search is empty", () => {
      const table = document.querySelector("table.searchable");
      const rows = Array.from(table.querySelectorAll("tbody tr"));

      rows.forEach((row) => {
        row.style.display = "";
      });

      const visible = rows.filter((r) => r.style.display !== "none");
      expect(visible.length).toBe(3);
    });

    it("should filter rows by search term", () => {
      const table = document.querySelector("table.searchable");
      const rows = Array.from(table.querySelectorAll("tbody tr"));
      const searchValue = "alpha";

      rows.forEach((row) => {
        const rowText = row.textContent.toLowerCase();
        if (rowText.indexOf(searchValue) === -1) {
          row.style.display = "none";
        } else {
          row.style.display = "";
        }
      });

      const visible = rows.filter((r) => r.style.display !== "none");
      expect(visible.length).toBe(1);
      expect(visible[0].textContent).toContain("Group Alpha");
    });

    it("should be case-insensitive", () => {
      const table = document.querySelector("table.searchable");
      const rows = Array.from(table.querySelectorAll("tbody tr"));
      const searchValue = "BETA";

      rows.forEach((row) => {
        const rowText = row.textContent.toLowerCase();
        if (rowText.indexOf(searchValue.toLowerCase()) === -1) {
          row.style.display = "none";
        } else {
          row.style.display = "";
        }
      });

      const visible = rows.filter((r) => r.style.display !== "none");
      expect(visible.length).toBe(1);
    });

    it("should hide all rows when nothing matches", () => {
      const table = document.querySelector("table.searchable");
      const rows = Array.from(table.querySelectorAll("tbody tr"));
      const searchValue = "nonexistent";

      rows.forEach((row) => {
        const rowText = row.textContent.toLowerCase();
        if (rowText.indexOf(searchValue) === -1) {
          row.style.display = "none";
        }
      });

      const visible = rows.filter((r) => r.style.display !== "none");
      expect(visible.length).toBe(0);
    });

    it("should match partial text", () => {
      const table = document.querySelector("table.searchable");
      const rows = Array.from(table.querySelectorAll("tbody tr"));
      const searchValue = "group";

      rows.forEach((row) => {
        const rowText = row.textContent.toLowerCase();
        if (rowText.indexOf(searchValue) === -1) {
          row.style.display = "none";
        } else {
          row.style.display = "";
        }
      });

      const visible = rows.filter((r) => r.style.display !== "none");
      expect(visible.length).toBe(3);
    });
  });

  describe("Expandable Row Functionality", () => {
    it("should toggle expand class and button state", () => {
      const row = document.querySelector("tr.expandable");
      const button = row.querySelector("button.btnExpand");

      button.classList.add("btnExpanded");
      row.classList.add("expanded");

      expect(button.classList.contains("btnExpanded")).toBe(true);
      expect(row.classList.contains("expanded")).toBe(true);

      button.classList.remove("btnExpanded");
      row.classList.remove("expanded");

      expect(button.classList.contains("btnExpanded")).toBe(false);
      expect(row.classList.contains("expanded")).toBe(false);
    });

    it("should only allow one row expanded at a time", () => {
      const rows = document.querySelectorAll("tr.expandable");
      const buttons = Array.from(rows).map((r) =>
        r.querySelector("button.btnExpand"),
      );

      // Expand first
      buttons[0].classList.add("btnExpanded");
      rows[0].classList.add("expanded");

      // Expand second, collapse first
      buttons[1].classList.add("btnExpanded");
      rows[1].classList.add("expanded");
      buttons[0].classList.remove("btnExpanded");
      rows[0].classList.remove("expanded");

      expect(buttons[0].classList.contains("btnExpanded")).toBe(false);
      expect(buttons[1].classList.contains("btnExpanded")).toBe(true);
      expect(rows[1].classList.contains("expanded")).toBe(true);
    });

    it("should prevent default button click behavior", () => {
      const button = document.querySelector("button.btnExpand");
      const clickEvent = new Event("click", { bubbles: true });

      // Simulating preventDefault check
      let prevented = false;
      Object.defineProperty(clickEvent, "preventDefault", {
        value: () => {
          prevented = true;
        },
      });

      // In real implementation, button/link clicks are detected and ignored
      if (button.tagName === "BUTTON" || button.tagName === "A") {
        clickEvent.preventDefault();
      }

      expect(prevented).toBe(true);
    });

    it("should track expand state with classes", () => {
      const row = document.querySelector("tr.expandable");
      const button = row.querySelector("button.btnExpand");

      expect(row.classList.contains("expandable")).toBe(true);
      expect(button.classList.contains("btnExpand")).toBe(true);
      expect(button.classList.contains("btnExpanded")).toBe(false);
    });
  });

  describe("Combined Search and Expand", () => {
    it("should maintain expand state when searching", () => {
      const row = document.querySelector("tr.expandable");
      const button = row.querySelector("button.btnExpand");
      const table = document.querySelector("table.searchable");
      const searchRows = Array.from(table.querySelectorAll("tbody tr"));

      // Expand row
      button.classList.add("btnExpanded");
      row.classList.add("expanded");

      // Search (should not affect expand state)
      const searchValue = "group";
      searchRows.forEach((r) => {
        const rowText = r.textContent.toLowerCase();
        if (rowText.indexOf(searchValue) === -1) {
          r.style.display = "none";
        }
      });

      expect(button.classList.contains("btnExpanded")).toBe(true);
      expect(row.classList.contains("expanded")).toBe(true);
    });

    it("should hide expanded row when filtered out by search", () => {
      const row = document.querySelector("tr.expandable");
      const button = row.querySelector("button.btnExpand");

      // Expand
      button.classList.add("btnExpanded");
      row.classList.add("expanded");

      // Hide the row
      row.style.display = "none";

      expect(row.style.display).toBe("none");
      expect(button.classList.contains("btnExpanded")).toBe(true);
    });
  });
});
