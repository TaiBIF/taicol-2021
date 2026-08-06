export default {
  guide: {
    title: "AI Import Tool - Guidelines & Workflow",
    fileUpload: "File Upload: PDF max 20MB. For larger files, retain only the title and taxon treatment pages.",
    redundancyCheck: "Redundancy Check: System validates reference and name usage before parsing.",
    scenariosTitle: "Validation Scenarios:",
    scenarios: {
      case1: "Case 1 (Existing/with usage): View reference directly; parsing skipped.",
      case2: "Case 2 (Existing/no usage): Link reference and parse.",
      case3: "Case 3 (Not Found): Add new reference, then parse.",
      case4: "Case 4 (Undetected): Manual link by user.",
      case5: "Case 5 (Similar): Link existing or create new, then parse."
    },
    newRef: "New Reference: Create new entry if absent; then parse.",
    parsingProcess: "Parsing Process: Automated notification sent via email. Results in \"My Checklists.\"",
    newTaxa: "New Taxa: New names are routed to \"Batch Name Addition.\"",
    verification: "Data Verification: Verify AI results against the original. Refine or complete manually as needed.",
    finalImport: "Final Import: One-time action. Post-import edits must be performed on the reference page."
  }
}