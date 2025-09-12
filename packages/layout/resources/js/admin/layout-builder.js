import sort from '@alpinejs/sort'

Alpine.plugin(sort)

export default function layoutBuilderComponent() {
    return {
        isReordering: false,

        isReorderingResources: [],

        isLoading: false,

        isAllCollapsed: null, // true = all collapsed, false = all expanded, null = mixed

        collapsedContainers: new Map(), // id => isCollapsed

        selectedRecords: this.$wire.$entangle('selectedRecords'),

        init() {
            this.$wire.on('layout-builder-reset', () => {
                this.isReordering = false
                this.isReorderingResources = []
            })

            window.addEventListener('keydown', (e) => {
                if (e.key !== 'Escape') {
                    return
                }
                this.isReordering = false
                this.isReorderingResources = []
            })

            window.addEventListener('container-collapsed-register', (e) => {
                this.collapsedContainers.set(
                    e.detail.id,
                    !!e.detail.isCollapsed,
                )
                this.updateIsAllCollapsed()
            })
            window.addEventListener('container-collapsed-changed', (e) => {
                this.collapsedContainers.set(
                    e.detail.id,
                    !!e.detail.isCollapsed,
                )
                this.updateIsAllCollapsed()
            })
        },

        selectAllRecords: async function (containerKey, widgetIndex) {
            this.isLoading = true

            this.selectedRecords[containerKey][widgetIndex] =
                await this.$wire.selectAllAssets(containerKey, widgetIndex)

            this.isLoading = false
        },

        deselectAllRecords: function (containerKey, widgetIndex) {
            this.selectedRecords[containerKey][widgetIndex] = []
        },

        collapseAll: function () {
            this.collapseAllComponents(true)
        },

        expandAll: function () {
            this.collapseAllComponents(false)
        },

        collapseAllWidgets: function (collapse) {
            this.$dispatch('collapse-widget', { isCollapsed: collapse })
        },

        collapseAllContainers: function (collapse) {
            this.$dispatch('collapse-container', { isCollapsed: collapse })
        },

        collapseAllComponents: function (collapse) {
            this.collapseAllWidgets(collapse)
            this.collapseAllContainers(collapse)
        },

        updateIsAllCollapsed: function () {
            const values = Array.from(this.collapsedContainers.values())
            if (values.length === 0) {
                this.isAllCollapsed = null
            } else if (values.every((v) => v === true)) {
                this.isAllCollapsed = true
            } else if (values.every((v) => v === false)) {
                this.isAllCollapsed = false
            } else {
                this.isAllCollapsed = null
            }
        },

        toggleReordering: function () {
            this.isReordering = !this.isReordering

            if (this.isReordering) {
                this.collapseAllWidgets(true)
            }
        },

        toggleReorderingResources: function (containerKey, widgetIndex) {
            this.deselectAllRecords(containerKey, widgetIndex)

            if (!this.isReorderingResources[containerKey]) {
                this.isReorderingResources[containerKey] = []

                this.isReorderingResources[containerKey][widgetIndex] = true

                return this.isReorderingResources[containerKey][widgetIndex]
            }

            this.isReorderingResources[containerKey][widgetIndex] =
                !this.isReorderingResources[containerKey][widgetIndex]

            return this.isReorderingResources[containerKey][widgetIndex]
        },

        isWidgetReorderingResources: function (containerKey, widgetIndex) {
            return this.isReorderingResources[containerKey]
                ? this.isReorderingResources[containerKey][widgetIndex]
                : false
        },
    }
}
