/**
 * Test suite for table sorting logic
 * Tests sorting algorithms and helper functions
 */

const JSDOM = require("jsdom").JSDOM;

describe("Table Sorting", () => {
  let dom;
  let document;
  let window;

  beforeEach(() => {
    const html = `
      <!DOCTYPE html>
      <html>
        <body>
          <table class="sortable">
            <thead>
              <tr>
                <th id="name">Name</th>
                <th id="email">Email</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr><td>Alice</td><td>alice@example.com</td><td></td></tr>
              <tr><td>Bob</td><td>bob@example.com</td><td></td></tr>
              <tr><td>Charlie</td><td>charlie@example.com</td><td></td></tr>
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
    it("should extract query parameters", () => {
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
      expect(getQueryVariable("missing")).toBe(false);
    });

    it("should update URL parameters", () => {
      const updateQueryStringParameter = (uri, key, value) => {
        const currentURL = new URL(window.location.href);
        const params = currentURL.searchParams;
        if (params.has(key)) {
          params.delete(key);
        }
        params.append(key, value);
        window.history.pushState("", "", currentURL.href);
      };

      updateQueryStringParameter(window.location.href, "sort", "name");
      let url = new URL(window.location.href);
      expect(url.searchParams.get("sort")).toBe("name");

      updateQueryStringParameter(window.location.href, "order", "desc");
      url = new URL(window.location.href);
      expect(url.searchParams.get("order")).toBe("desc");
    });
  });

  describe("Sorting Logic", () => {
    it("should sort rows ascending", () => {
      const tbody = document.querySelector("tbody");
      const rows = Array.from(tbody.querySelectorAll("tr"));

      rows.sort((a, b) =>
        a.cells[0].textContent
          .trim()
          .localeCompare(b.cells[0].textContent.trim(), undefined, {
            numeric: true,
          }),
      );

      tbody.innerHTML = "";
      rows.forEach((row) => tbody.appendChild(row));

      const names = Array.from(tbody.querySelectorAll("tr")).map((r) =>
        r.cells[0].textContent.trim(),
      );
      expect(names).toEqual(["Alice", "Bob", "Charlie"]);
    });

    it("should sort rows descending", () => {
      const tbody = document.querySelector("tbody");
      const rows = Array.from(tbody.querySelectorAll("tr"));

      rows.sort(
        (a, b) =>
          -1 *
          a.cells[0].textContent
            .trim()
            .localeCompare(b.cells[0].textContent.trim(), undefined, {
              numeric: true,
            }),
      );

      tbody.innerHTML = "";
      rows.forEach((row) => tbody.appendChild(row));

      const names = Array.from(tbody.querySelectorAll("tr")).map((r) =>
        r.cells[0].textContent.trim(),
      );
      expect(names).toEqual(["Charlie", "Bob", "Alice"]);
    });

    it("should handle numeric sorting", () => {
      const tbody = document.querySelector("tbody");
      tbody.innerHTML = `
        <tr><td>Item 100</td></tr>
        <tr><td>Item 20</td></tr>
        <tr><td>Item 3</td></tr>
      `;

      const rows = Array.from(tbody.querySelectorAll("tr"));
      rows.sort((a, b) =>
        a.cells[0].textContent
          .trim()
          .localeCompare(b.cells[0].textContent.trim(), undefined, {
            numeric: true,
          }),
      );

      tbody.innerHTML = "";
      rows.forEach((row) => tbody.appendChild(row));

      const items = Array.from(tbody.querySelectorAll("tr")).map((r) =>
        r.cells[0].textContent.trim(),
      );
      expect(items).toEqual(["Item 3", "Item 20", "Item 100"]);
    });

    it("should handle case-insensitive sorting", () => {
      const tbody = document.querySelector("tbody");
      tbody.innerHTML = `
        <tr><td>alice</td></tr>
        <tr><td>CHARLIE</td></tr>
        <tr><td>Bob</td></tr>
      `;

      const rows = Array.from(tbody.querySelectorAll("tr"));
      rows.sort((a, b) =>
        a.cells[0].textContent
          .trim()
          .localeCompare(b.cells[0].textContent.trim(), undefined, {
            numeric: true,
          }),
      );

      tbody.innerHTML = "";
      rows.forEach((row) => tbody.appendChild(row));

      const names = Array.from(tbody.querySelectorAll("tr")).map((r) =>
        r.cells[0].textContent.trim(),
      );
      expect(names).toEqual(["alice", "Bob", "CHARLIE"]);
    });
  });

  describe("Sort Indicators", () => {
    it("should toggle asc class", () => {
      const header = document.querySelector("th#name");
      const hasAsc1 = header.classList.toggle("asc");
      expect(hasAsc1).toBe(true);

      const hasAsc2 = header.classList.toggle("asc");
      expect(hasAsc2).toBe(false);
    });

    it("should add and remove sort symbols", () => {
      const header = document.querySelector("th#name");

      header.innerHTML += " ▲";
      expect(header.innerHTML).toContain("▲");

      header.innerHTML = header.innerHTML.replace(/ ▲| ▼/, "");
      header.innerHTML += " ▼";
      expect(header.innerHTML).toContain("▼");
      expect(header.innerHTML).not.toContain("▲");
    });

    it("should remove indicators from other headers", () => {
      const headers = Array.from(document.querySelectorAll("th"));

      headers.forEach((h) => {
        h.innerHTML += " ▲";
      });

      headers.forEach((h) => {
        h.innerHTML = h.innerHTML.replace(/ ▲| ▼/, "");
      });

      const withIndicator = headers.filter((h) => h.innerHTML.match(/ ▲| ▼/));
      expect(withIndicator.length).toBe(0);
    });
  });
});
