/*
 * File: app/view/PartUploadWindow.js
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

Ext.define('VMS.view.PartUploadWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.partUploadWindow',

    requires: [
        'VMS.view.S3Upload'
    ],

    controller: 'VMS.controller.PartUploadViewController',
    itemId: 'partUploadWindow',
    width: 200,
    resizable: false,
    title: '',
    minimizable: true,

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [
                {
                    xtype: 'component',
                    itemId: 'content',
                    tpl: [
                        '<ul class="upload_info">',
                        '    <li>',
                        '        <span class="header">Bucket</span>',
                        '        <span class="value">{bucket}</span>',
                        '    </li>',
                        '    <li>',
                        '        <span class="header">Key</span>',
                        '        <span class="value">{key}</span>',
                        '    </li>',
                        '    <li>',
                        '        <span class="header">File Size</span>',
                        '        <span class="value">{[new Number(values.size/1024/1024).toFixed(2).toLocaleString()]}MB</span>',
                        '    </li>',
                        '</ul>'
                    ]
                },
                {
                    xtype: 'progressbar',
                    itemId: 'progress',
                    margin: '0 5 0 5',
                    value: 0.4
                },
                {
                    xtype: 's3upload',
                    itemId: 'upload'
                }
            ]
        });

        me.callParent(arguments);
    }

});