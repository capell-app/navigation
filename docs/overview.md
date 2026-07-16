# Navigation

<!-- prettier-ignore-start -->

## What it does for you

Navigation is how you build the menus visitors use to move around a site. Create a menu for a particular site and language, choose the menu key that the theme renders, then add and arrange its items in the menu editor.

## Create a menu

Go to **Websites → Navigation** or open the **Navigations** relation on a site. Give the menu a name, choose its **Key**, site, and language, then save it. The built-in keys are **Main**, **Footer**, and **Sub-footer**; selecting a key is how the theme identifies which menu to render. There is no separate Header or Footer assignment control.

Use the list filters to find menus by site, language, or key. Site-scoped admins see global menus and menus for their assigned sites, not menus belonging to other sites.

## Build menu items

In the **Items** tab, use **Add navigation item** and choose the type that fits:

- **Page** for a page or another pageable record; turn on **Auto children** only when child pages should supply the submenu.
- **Link** or **External link** for a safe URL. Set the target when it should open in a new context.
- **Heading** for a non-link label that groups surrounding menu items.

Drag items to reorder them or nest them below another item. A parent item can use a standard dropdown or a mega-menu layout; mega menus also expose column, panel-heading, description, and optional panel-link fields.

## Control who sees an item

Each item can be visible to everyone, guests, authenticated visitors, a named ability, or a role. Use the latter two only when the site's access rules already define that ability or role. You can also set the active-link matching mode and the `rel` attribute for a link.

## Good to know

- Page links follow the selected page record; external links are validated as safe URLs.
- The theme decides how a menu key is rendered. If a custom theme does not show a menu, confirm it asks for the same key before changing the menu content.
- The editor also has optional component/component-item fields for a theme-specific navigation renderer; leave them unchanged unless your developer has supplied the component names.

---

For how to use Navigation, see the [admin guide](admin-guide.md).
For developers: see the [README](../README.md).

<!-- prettier-ignore-end -->
