/*
 * File: app/controller/WorkplaceSectionController.js
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

Ext.define('VMS.controller.WorkplaceSectionController', {
    extend: 'Ext.app.Controller',

    stores: [
        'MultipartUploadStore',
        'S3ResourceStore',
        'TagStore'
    ],
    views: [
        'WorkplaceSection',
        'PartUploadPanel',
        'UploadPanel'
    ],

    refs: [
        {
            ref: 'mainBody',
            selector: '#mainBody'
        },
        {
            ref: 'resourceTreeSection',
            selector: '#workplaceSection #resourceSection #treePanel',
            xtype: 'Ext.tree.Panel'
        },
        {
            ref: 'resourceSection',
            selector: '#workplaceSection #resourceSection'
        }
    ],

    onClickOnSection: function(button, e, eOpts) {
        if (button.getItemId() === "inProgress") {	
            this.getMainBody().removeAll();
            this.getMainBody().add(this.getView('PartUploadPanel').create());
        } else if (button.getItemId() === "myComplete" || button.getItemId() === "othersComplete") {
            this.getMainBody().removeAll();
            this.getMainBody().add(this.getView('UploadPanel').create());
        }
    },

    onComponentUploadFinish: function(eventOptions) {
        this.getStore('S3ResourceStore').reload();
    },

    onTextfieldKeypress: function(textfield, e, eOpts) {
        if (e.getKey() == Ext.EventObject.ENTER) {
            if (textfield.getValue().length > 0) {
                var value = textfield.getValue();
                textfield.setValue('');
                var found = false;
                var view = this.getResourceTreeSection().getView();
                var store = this.getStore('TagStore');
                store.getRootNode().cascadeBy(function() {
                    if (this.get('name') == value) {
                        found = true;
                        view.focusNode(this);
                        return false;
                    }
                });
                if (!found) {
                    var node = new VMS.model.Tag({
                        name: value
                    });
                    store.suspendAutoSync();
                    this.getResourceSection().setLoading(true);
                    store.getRootNode().appendChild(node);
                    store.sync({
                        success: function() {
                            this.getResourceSection().setLoading(false);
                            store.resumeAutoSync();
                        },
                        failure: function() {
                            store.getRootNode().removeChild(node, true);
                            Ext.MessageBox.alert('Error', 'Unable to add tag');
                            this.getResourceSection().setLoading(false);
                            store.resumeAutoSync();
                        },
                        scope: this
                    });
                } else {
                    Ext.MessageBox.alert('Warning', 'Tag with the same name already exist');
                }
            }
        }
    },

    onTreepanelCellkeydown: function(tableview, td, cellIndex, record, tr, rowIndex, e, eOpts) {
        switch (e.getKey()) {
            case Ext.EventObject.DELETE:
            Ext.MessageBox.confirm('Tag Deletion', 'Are you sure?', function() {
            });
            break;
            case Ext.EventObject.NUM_PLUS:
            case Ext.EventObject.ENTER:
            tableview.expand(record, true);
            break;
            case Ext.EventObject.NUM_MINUS:
            tableview.collapse(record, true);
            break;
        }
    },

    onViewDrop: function(target, data, record, position) {
        console.log(target);
        console.log(data);
        console.log(record);
        console.log(position);
        record.set("count", record.get("count")+data.records.length);
    },

    init: function(application) {
        this.control({
            "#workplaceSection #uploadSection menuitem": {
                click: this.onClickOnSection
            },
            "#uploadSection s3upload": {
                uploadFinish: this.onComponentUploadFinish
            },
            "#workplaceSection #newTag": {
                keypress: this.onTextfieldKeypress
            },
            "#resourceSection #treePanel": {
                cellkeydown: this.onTreepanelCellkeydown
            },
            "#treePanel treeview": {
                drop: this.onViewDrop
            }
        });
    }

});
