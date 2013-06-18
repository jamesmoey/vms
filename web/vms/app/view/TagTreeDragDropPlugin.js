/*
 * File: app/view/TagTreeDragDropPlugin.js
 *
 * This file was generated by Sencha Architect version 2.2.2.
 * http://www.sencha.com/products/architect/
 *
 * This file requires use of the Ext JS 4.2.x library, under independent license.
 * License of Sencha Architect does not include license for Ext JS 4.2.x. For more
 * details see http://www.sencha.com/license or contact license@sencha.com.
 *
 * This file will be auto-generated each and everytime you save your project.
 *
 * Do NOT hand edit this file.
 */

Ext.define('VMS.view.TagTreeDragDropPlugin', {
    extend: 'Ext.tree.plugin.TreeViewDragDrop',

    constructor: function(cfg) {
        var me = this;
        cfg = cfg || {};
        me.callParent([Ext.apply({
        }, cfg)]);
    },

    onViewRender: function(view) {
        this.callParent([view]);
        this.dropZone.addToGroup("resource");
        this.dropZone.handleNodeDrop = function(data, targetNode, position) {
            var me = this,
                targetView = me.view,
                parentNode = targetNode ? targetNode.parentNode : targetView.panel.getRootNode(),
                Model = targetView.getStore().treeStore.model,
                records, i, len, record,
                insertionMethod, argList,
                needTargetExpand,
                transferData;

            // If the copy flag is set, create a copy of the models
            if (data.copy) {
                records = data.records;
                data.records = [];
                for (i = 0, len = records.length; i < len; i++) {
                    record = records[i];
                    if (record.isNode) {
                        data.records.push(record.copy(undefined, true));
                    } else {
                        // If it's not a node, make a node copy
                        data.records.push(new Model(record.data, record.getId()));
                    }
                }
            }

            // Cancel any pending expand operation
            me.cancelExpand();

            if (data.records[0] instanceof VMS.model.Tag) {
                // Grab a reference to the correct node insertion method.
                // Create an arg list array intended for the apply method of the
                // chosen node insertion method.
                // Ensure the target object for the method is referenced by 'targetNode'
                if (position == 'before') {
                    insertionMethod = parentNode.insertBefore;
                    argList = [null, targetNode];
                    targetNode = parentNode;
                }
                else if (position == 'after') {
                    if (targetNode.nextSibling) {
                        insertionMethod = parentNode.insertBefore;
                        argList = [null, targetNode.nextSibling];
                    }
                    else {
                        insertionMethod = parentNode.appendChild;
                        argList = [null];
                    }
                    targetNode = parentNode;
                }
                else {
                    if (!(targetNode.isExpanded() || targetNode.isLoading())) {
                        needTargetExpand = true;
                    }
                    insertionMethod = targetNode.appendChild;
                    argList = [null];
                }

                // A function to transfer the data into the destination tree
                transferData = function() {
                    var color,
                        n;

                    // Coalesce layouts caused by node removal, appending and sorting
                    Ext.suspendLayouts();

                    targetView.getSelectionModel().clearSelections();

                    // Insert the records into the target node
                    for (i = 0, len = data.records.length; i < len; i++) {
                        record = data.records[i];
                        if (!record.isNode) {
                            if (record.isModel) {
                                record = new Model(record.data, record.getId());
                            } else {
                                record = new Model(record);
                            }
                            data.records[i] = record;
                        }
                        argList[0] = record;
                        insertionMethod.apply(targetNode, argList);
                    }

                    // If configured to sort on drop, do it according to the TreeStore's comparator
                    if (me.sortOnDrop) {
                        targetNode.sort(targetNode.getOwnerTree().store.generateComparator());
                    }

                    Ext.resumeLayouts(true);

                    // Kick off highlights after everything's been inserted, so they are
                    // more in sync without insertion/render overhead.
                    // Element.highlight can handle highlighting table nodes.
                    if (Ext.enableFx && me.dropHighlight) {
                        color = me.dropHighlightColor;

                        for (i = 0; i < len; i++) {
                            n = targetView.getNode(data.records[i]);
                            if (n) {
                                Ext.fly(n).highlight(color);
                            }
                        }
                    }
                };

                // If dropping right on an unexpanded node, transfer the data after it is expanded.
                if (needTargetExpand) {
                    targetNode.expand(false, transferData);
                }
                // If the node is waiting for its children, we must transfer the data after the expansion.
                // The expand event does NOT signal UI expansion, it is the SIGNAL for UI expansion.
                // It's listened for by the NodeStore on the root node. Which means that listeners on the target
                // node get notified BEFORE UI expansion. So we need a delay.
                // TODO: Refactor NodeInterface.expand/collapse to notify its owning tree directly when it needs to expand/collapse.
                else if (targetNode.isLoading()) {
                    targetNode.on({
                        expand: transferData,
                        delay: 1,
                        single: true
                    });
                }
                // Otherwise, call the data transfer function immediately
                else {
                    transferData();
                }
            }
        }
    }

});