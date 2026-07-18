# Navigation

<!-- prettier-ignore-start -->

## What it does for you

Navigation is how you build the menus visitors use to move around a site. Create a menu for a particular site and language, choose the menu key that the theme renders, then add and arrange its items in the menu editor.

## Initial setup

Install the package and run its migrations before creating menus. An integrator can then run `capell:navigation-setup`; use `--sites="Site name,Another site"` to limit setup to named sites. Setup creates or updates the **Main**, **Footer**, and **Sub-footer** menus for each selected site. It skips a site that does not yet have a home page, so create the site home page first and rerun setup if needed.

## Create a menu

Go to **Websites → Navigation** or open the **Navigations** relation on a site. Give the menu a name, choose its **Key**, site, and language, then save it. The built-in keys are **Main**, **Footer**, and **Sub-footer**; selecting a key is how the theme identifies which menu to render. There is no separate Header or Footer assignment control.

A key can be used only once for the same site and language. Use a language-specific menu when its labels or structure differ; a menu without a language acts as a fallback. At render time, Capell prefers an eligible menu for the current site over a global menu. A language-specific match is used when available.

Use the list filters to find menus by site, language, or key. Site-scoped admins see global menus and menus for their assigned sites, not menus belonging to other sites.

## Build menu items

In the **Items** tab, use **Add navigation item** and choose the type that fits:

- **Page** for a page or another pageable record; turn on **Auto children** only when child pages should supply the submenu.
- **Link** or **External link** for a safe URL. Set the target when it should open in a new context.
- **Heading** for a non-link label that groups surrounding menu items.

Drag items to reorder them or nest them below another item. A parent item can use a standard dropdown or a mega-menu layout; mega menus also expose column, panel-heading, description, and optional panel-link fields.

The **Navigation** tab on a page shows the menus that currently reference that page and links back to their editors. Page items resolve their public URL from the selected page, so changing a page URL does not require replacing the menu item.

## Control who sees an item

Each item can be visible to everyone, guests, authenticated visitors, a named ability, or a role. Use the latter two only when the site's access rules already define that ability or role. You can also set the active-link matching mode and the `rel` attribute for a link.

Item visibility only controls whether the link is rendered. It does not protect the destination: enforce authentication, roles, or abilities on the target route or content as well.

## Publish and retire a menu

Use the publish controls to publish immediately, schedule a future **Visible from** time, or set **Visible until**. Pending and expired menus are excluded when the frontend resolves a menu. These dates are checked when the menu is requested, so no scheduler task is needed for a menu to start or stop rendering.

Saving, deleting, or restoring a menu invalidates its frontend caches. Page URL changes also invalidate affected navigation render data, so a successful save should not need a manual cache clear.

## Copy, delete, and recover

When cloning a site, enable **Copy navigations** if its menus should be copied. Page items are remapped when the corresponding pages are copied; review the cloned menus when pages were not copied or were replaced through a different workflow.

A normal delete is recoverable through the trashed filter and **Restore** action. **Force delete** is permanent. Site-scoped admins can view global menus but only global admins can update, delete, restore, or force-delete them; the corresponding Navigation permissions are still required for each action.

## Good to know

- Page links follow the selected page record; external links are validated as safe URLs.
- The theme decides how a menu key is rendered. If a custom theme does not show a menu, confirm it asks for the same key before changing the menu content.
- The editor also has optional component/component-item fields for a theme-specific navigation renderer; leave them unchanged unless your developer has supplied the component names.

---

For how to use Navigation, see the [admin guide](admin-guide.md).
For developers: see the [README](../README.md).

<!-- prettier-ignore-end -->
