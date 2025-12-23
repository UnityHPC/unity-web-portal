# JavaScript Table Testing Suite

This directory contains automated tests for the table sorting, filtering, and search functionality in the Unity Web Portal.

## Overview

The test suite covers three main JavaScript modules:

- **sort.test.js** - Tests for table column sorting (`webroot/js/sort.js`)
- **filter.test.js** - Tests for table filtering (`webroot/js/filter.js`)
- **tables.test.js** - Tests for expandable tables and search (`webroot/js/tables.js`)

## Test Framework

- **Jest** - JavaScript testing framework
- **JSDOM** - JavaScript implementation of web standards for testing in Node.js

## Installation

Install the required dependencies:

```bash
npm install
```

## Running Tests

### Run all JavaScript tests

```bash
npm test
```

### Run tests in watch mode (reruns on file changes)

```bash
npm run test:watch
```

### Run only JS tests

```bash
npm run test:js
```

## Test Coverage

The test suite includes comprehensive coverage for:

### Sort Functionality (sort.test.js)

- ✅ Query parameter extraction from URL
- ✅ Column sorting (ascending/descending)
- ✅ Numeric sorting
- ✅ Case-insensitive sorting
- ✅ Sort direction toggle
- ✅ Sort indicator symbols (▲ ▼)
- ✅ Actions column exclusion from sorting
- ✅ URL parameter updates
- ✅ Preventing sort on Actions column
- ✅ Sort symbol removal from other columns

### Filter Functionality (filter.test.js)

- ✅ Query parameter extraction
- ✅ URL parameter updates
- ✅ Row visibility toggling
- ✅ Partial string matching
- ✅ Case-insensitive filtering
- ✅ No matches handling
- ✅ Filter input display/hiding
- ✅ Placeholder text generation
- ✅ Filter value persistence
- ✅ Different filter types (UID, Name, Department)
- ✅ Filter with sorted tables

### Table Search & Expand (tables.test.js)

- ✅ Search input filtering
- ✅ Case-insensitive search
- ✅ Partial text matching
- ✅ Real-time search updates
- ✅ Expandable row toggle
- ✅ Single expansion (only one row at a time)
- ✅ Button/link click handling
- ✅ State indicators
- ✅ Expand state with search

## Test Organization

```
test/
├── js/
│   ├── setup.js                 # Jest configuration
│   ├── sort.test.js             # Sort functionality tests
│   ├── filter.test.js           # Filter functionality tests
│   └── tables.test.js           # Table search/expand tests
```

## Example Test Case

```javascript
it("should sort table by name column in ascending order", () => {
  const table = document.querySelector("table.sortable");
  const nameHeader = document.querySelector("th#name");
  const tbody = table.querySelector("tbody");

  nameHeader.click();

  const sortedNames = Array.from(tbody.querySelectorAll("tr")).map((r) =>
    r.cells[0].textContent.trim(),
  );
  expect(sortedNames).toEqual(["Alice Johnson", "Bob Smith", "Charlie Brown"]);
  expect(nameHeader.classList.contains("asc")).toBe(true);
});
```

## Writing New Tests

When adding new tests:

1. Create a test case using the `describe()` block for grouping related tests
2. Use `it()` for individual test cases
3. Set up DOM elements in `beforeEach()`
4. Use `expect()` for assertions

### Key Testing Patterns

**Testing DOM elements:**

```javascript
const element = document.querySelector(".selector");
expect(element).toBeTruthy();
```

**Testing class toggling:**

```javascript
element.click();
expect(element.classList.contains("asc")).toBe(true);
```

**Testing URL parameters:**

```javascript
const url = new URL(window.location.href);
expect(url.searchParams.get("sort")).toBe("name");
```

**Testing array transformations:**

```javascript
const values = Array.from(rows).map((r) => r.cells[0].textContent);
expect(values).toEqual(["expected", "values"]);
```

## Debugging Tests

### Run a single test file:

```bash
npx jest test/js/sort.test.js
```

### Run tests matching a pattern:

```bash
npx jest --testNamePattern="should sort"
```

### Run with verbose output:

```bash
npx jest --verbose
```

### Enable debugging in VS Code:

```json
{
  "type": "node",
  "request": "launch",
  "program": "${workspaceFolder}/node_modules/.bin/jest",
  "args": ["--runInBand"],
  "console": "integratedTerminal"
}
```

## Common Issues

### Tests fail with "document is not defined"

- Ensure `testEnvironment: 'jsdom'` is set in `jest.config.js`

### Query parameters not working correctly

- Remember to use `window.history.pushState()` to update the URL in tests

### Elements not found in DOM

- Check that the DOM structure in `beforeEach()` matches your HTML structure
- Use `document.body.innerHTML` for debugging

## CI/CD Integration

To integrate with CI/CD pipelines (GitHub Actions, GitLab CI, etc.):

```yaml
# GitHub Actions example
- name: Run JavaScript Tests
  run: npm run test:js
```

## Resources

- [Jest Documentation](https://jestjs.io/)
- [JSDOM Documentation](https://github.com/jsdom/jsdom)
- [Testing Library Best Practices](https://testing-library.com/)

## Contributing

When adding new features to the JavaScript:

1. Write tests first (TDD approach recommended)
2. Ensure all tests pass: `npm test`
3. Update this README if adding new test categories
