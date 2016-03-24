## Grant Funding, Tenders; Tracking Prospects and Contracts

This can help you track your funding efforts and assist with making sure deadlines are met.

*   A **Report** shows all live prospects/contracts and the next action that is due.
*   Going to a (potential) funder's **Activities** tab will show you exactly where you're at with it.
*   Going to your own **Activities report** will show you what you're responsible for!

## How to use it

It does this using three activity types:

1.  **Prospect** this is used to record information on a grant proposal, tender etc. It has a **stage** field that tracks the prospect from initial idea through to successful/unsuccessful finish.
2.  **Contract** this is used for successful prospects.
3.  **Get in touch** this is used for **follow-up** activities to either a Prospect or a Contract.

## Prospect activities

This activity has the following **stages**:

*  **Speculative / Seeking invitation**.

*  **Writing proposal / tender.** Use this when you have made the decision to
apply, and there is nothing stopping the work going ahead. Schedule a follow-up
Get In Touch activity to record submission deadlines etc.

*  **Awaiting Result.** The proposal/tender has been submitted and you're now
just waiting. Schedule follow-up Get In Touch activity for the date you should
hear by, then you can chase them after that.

*  **Successful** Hooray. You got funded! You should create a **Contract**
activity at this stage.

*  **Unsuccessful**. Gah! Better luck next time.

*  **Dropped by us**. For whatever reason you chose to stop the process.
Record details in the Details field.

*  **Negotiating**. Sometimes things aren't as simple and you need a holding
or in-between state. This is here for those times.

Note: You should leave a prospect as status: **Scheduled** until it completes
(un/successful or dropped), at which point mark it **Completed**.

## Contract activity

Add one of these activity on successful completion of the prospect. Set the
**status** to **Live** until the entire contract is completed, at which point
update it to **Completed.**

Add in any details required. Use the subject for the name of the project again. Assign the contract to a member of staff.

Then schedule as many Get In Touch follow up activities as are needed to record significant timelines, for example:

*   Milestone such-and-such to be reached.
*   Year 1 report due from us
*   Funding tranche 1 due from them
*   Year 2 report due from us
*   Funding tranche 2 due from them
*   End of project report due.

## Get in touch activities

*   Always add these as **follow-up** activities from either a prospect or a contract.
*   You can assign each of these to the appropriate member(s) of staff.
*   Update them to status **Completed** when done.
*   Use the Details field to record anything specific, but it might be helpful to keep most of the key information on either the prospect or the contract activity.

# Technical Notes

This creates a couple of activity types on installation, which are referred to
in the code by their unique names.

- `pelf_prospect_activity_type` Activity type.

  This activity has a custom field group named `pelf_prospect` which contains:

  - `pelf_stage` Select field. Uses an option group named `pelf_stage_opts`.
  - `pelf_est_worth` Text float field.

- `pelf_contract_activity_type` Activity type.

  This activity type has a customfield group named `pelf_contract` which contains:

  - `pelf_total_worth` Text float field.

