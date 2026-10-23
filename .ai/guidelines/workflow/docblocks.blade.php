# Docblocks — Record the Why, Not the What

A docblock earns its place when it records something the code cannot show: a business rule, a
decision, or the reason behind a non-obvious line. The commit message and the PR are not enough,
because nobody reads them when they open the file to change it.

## Keep

- **Why a line is the way it is**, when changing it would bring a bug back: "Locks the agenda
  before deleting the previous block: deleting first lets two transactions wait on each other's
  deleted rows (deadlock)."
- **A rule or a contract the reader needs**: "Always 200, even when the first answer creates the
  row: for the app this is just 'save answers'."
- **Where a decision came from**, when it is not visible in the code: "Same rule as the app's
  `isUpcoming()`."
- Type information: `@param`, `@return`, `@throws`, array shapes.

## Leave out

- Prose that narrates what the code already says: "Builds what the `MeResource` needs.", "Lists the
  employee's appointments.", "Run the migrations."
- A summary of a class or DTO whose name and properties already say it.
- Inline comments in the middle of a flow; if the explanation is needed, it goes in the docblock
  above the method.
