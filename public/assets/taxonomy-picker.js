/**
 * QuickWP v2 - Taxonomy Picker
 * 
 * Loads categories, tags, and pages via WP REST API and renders checkboxes/dropdowns.
 * Usage: Call TaxonomyPicker.init(config) where config contains endpoint URLs.
 */
(function(window) {
    'use strict';

    var TaxonomyPicker = {
        /**
         * Initialize all pickers based on config
         * @param {Object} config - {categoriesEndpoint, tagsEndpoint, pagesEndpoint}
         */
        init: function(config) {
            document.addEventListener('DOMContentLoaded', function() {
                // Categories picker (checkboxes)
                if (config.categoriesEndpoint) {
                    TaxonomyPicker.initCheckboxPicker(
                        'category',
                        config.categoriesEndpoint,
                        'categories',
                        'categories-picker'
                    );
                }

                // Tags picker (checkboxes)
                if (config.tagsEndpoint) {
                    TaxonomyPicker.initCheckboxPicker(
                        'tag',
                        config.tagsEndpoint,
                        'tags',
                        'tags-picker'
                    );
                }

                // Parent pages picker (dropdown)
                if (config.pagesEndpoint) {
                    TaxonomyPicker.initPagePicker(
                        config.pagesEndpoint,
                        'parent_id',
                        'parent-picker'
                    );
                }
            });
        },

        /**
         * Initialize a checkbox-based picker (categories/tags)
         */
        initCheckboxPicker: function(kind, endpoint, inputId, containerId) {
            var input = document.getElementById(inputId);
            var container = document.getElementById(containerId);
            if (!input || !container) return;

            function setLoading(text) {
                container.innerHTML = '<small class="picker-empty">' + text + '</small>';
            }

            setLoading('Loading ' + kind + 's...');

            // Build URL with pagination and minimal fields
            var url = endpoint;
            var sep = url.indexOf('?') === -1 ? '?' : '&';
            url += sep + 'per_page=100&_fields=id,name&orderby=name&order=asc';

            fetch(url)
                .then(function(resp) {
                    if (!resp.ok) throw new Error('HTTP ' + resp.status);
                    return resp.json();
                })
                .then(function(items) {
                    if (!Array.isArray(items) || !items.length) {
                        setLoading('No ' + kind + 's found.');
                        return;
                    }

                    // Parse currently selected IDs
                    var selectedIds = new Set();
                    if (input.value.trim() !== '') {
                        input.value.split(',').forEach(function(part) {
                            var v = parseInt(part, 10);
                            if (v > 0) selectedIds.add(String(v));
                        });
                    }

                    container.innerHTML = '';

                    // Sync checkboxes to input
                    function syncFromChecks() {
                        var checked = container.querySelectorAll('input[type="checkbox"]:checked');
                        var ids = Array.prototype.map.call(checked, function(cb) { return cb.value; });
                        input.value = ids.join(', ');
                    }

                    // Build checkboxes
                    items.forEach(function(item) {
                        var id = String(item.id);
                        var name = item.name || ('#' + id);

                        var label = document.createElement('label');
                        label.className = 'picker-chip';

                        var cb = document.createElement('input');
                        cb.type = 'checkbox';
                        cb.value = id;
                        if (selectedIds.has(id)) cb.checked = true;
                        cb.addEventListener('change', syncFromChecks);

                        label.appendChild(cb);
                        label.appendChild(document.createTextNode(' ' + name + ' '));
                        
                        var idSpan = document.createElement('span');
                        idSpan.className = 'picker-id';
                        idSpan.textContent = '(' + id + ')';
                        label.appendChild(idSpan);
                        
                        container.appendChild(label);
                    });

                    syncFromChecks();
                })
                .catch(function(err) {
                    setLoading('Could not load ' + kind + 's. Enter IDs manually.');
                    if (window.console && console.error) {
                        console.error('Taxonomy fetch failed for ' + kind, err);
                    }
                });
        },

        /**
         * Initialize a dropdown picker for parent pages
         */
        initPagePicker: function(endpoint, inputId, containerId) {
            var input = document.getElementById(inputId);
            var container = document.getElementById(containerId);
            if (!input || !container) return;

            function setLoading(text) {
                container.innerHTML = '<small class="picker-empty">' + text + '</small>';
            }

            setLoading('Loading pages...');

            // Fetch published pages
            var url = endpoint;
            var sep = url.indexOf('?') === -1 ? '?' : '&';
            url += sep + 'per_page=100&_fields=id,title,slug,parent&orderby=title&order=asc&status=publish';

            fetch(url)
                .then(function(resp) {
                    if (!resp.ok) throw new Error('HTTP ' + resp.status);
                    return resp.json();
                })
                .then(function(pages) {
                    if (!Array.isArray(pages) || !pages.length) {
                        setLoading('No pages found.');
                        return;
                    }

                    container.innerHTML = '';

                    // Build dropdown
                    var select = document.createElement('select');
                    select.className = 'picker-select';

                    // Default option
                    var defaultOpt = document.createElement('option');
                    defaultOpt.value = '0';
                    defaultOpt.textContent = '— No parent (top-level) —';
                    select.appendChild(defaultOpt);

                    // Add pages organized by hierarchy
                    var tree = TaxonomyPicker.buildPageTree(pages);
                    TaxonomyPicker.addPageOptions(select, tree, 0);

                    // Sync selection
                    var currentVal = parseInt(input.value, 10) || 0;
                    select.value = String(currentVal);

                    select.addEventListener('change', function() {
                        input.value = select.value;
                    });

                    container.appendChild(select);

                    // Also show quick reference
                    var info = document.createElement('div');
                    info.className = 'picker-info';
                    info.innerHTML = '<small>' + pages.length + ' pages available</small>';
                    container.appendChild(info);
                })
                .catch(function(err) {
                    setLoading('Could not load pages. Enter ID manually.');
                    if (window.console && console.error) {
                        console.error('Page fetch failed', err);
                    }
                });
        },

        /**
         * Build a tree structure from flat pages array
         */
        buildPageTree: function(pages) {
            var map = {};
            var roots = [];

            // Create map
            pages.forEach(function(p) {
                map[p.id] = {
                    id: p.id,
                    title: p.title && p.title.rendered ? p.title.rendered : ('Page #' + p.id),
                    slug: p.slug || '',
                    parent: p.parent || 0,
                    children: []
                };
            });

            // Build tree
            pages.forEach(function(p) {
                var node = map[p.id];
                if (node.parent && map[node.parent]) {
                    map[node.parent].children.push(node);
                } else {
                    roots.push(node);
                }
            });

            return roots;
        },

        /**
         * Add page options to select with indentation
         */
        addPageOptions: function(select, nodes, depth) {
            var prefix = '';
            for (var i = 0; i < depth; i++) {
                prefix += '— ';
            }

            nodes.forEach(function(node) {
                var opt = document.createElement('option');
                opt.value = String(node.id);
                opt.textContent = prefix + node.title + ' (ID: ' + node.id + ', slug: ' + node.slug + ')';
                select.appendChild(opt);

                if (node.children.length > 0) {
                    TaxonomyPicker.addPageOptions(select, node.children, depth + 1);
                }
            });
        }
    };

    // Export
    window.TaxonomyPicker = TaxonomyPicker;
})(window);
