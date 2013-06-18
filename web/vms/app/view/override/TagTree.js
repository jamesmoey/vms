Ext.define('VMS.view.override.TagTree', {
    override: 'VMS.view.TagTree',
    initComponent: function() {
        var me = this;
        Ext.applyIf(me, {
            viewConfig: {
                plugins: [
                    Ext.create('VMS.view.TagTreeDragDropPlugin', {
                        appendOnly: true,
                        dragGroup: 'tag',
                        dropGroup: 'tag'
                    })
                ]
            }
        });
        me.callParent(arguments);
    }
});