Ext.define('EasyDateColumn', {
    extend: 'Ext.grid.column.Column',
    alias: ['widget.easydatecolumn'],
    requires: ['Ext.Date'],

    /**
     * @cfg {String} recentFormat
     * A formatting string as used by {@link Ext.Date#format} to format a Date for this Column that is between yesterday
     * and tomorrow.
     *
     * Default to "H:i:s" e.g 11:14:34  or  Tomorrow, 05:23:12  or  Yesterday, 14:21:43
     */

    /**
     * @cfg {String} weekFormat
     * A formatting string as used by {@link Ext.Date#format} to format a Date for this Column that is within 1 week
     * timeframe.
     *
     * Default to "l, H:i" e.g Sunday, 11:14
     */

    /**
     * @cfg {String} monthFormat
     * A formatting string as used by {@link Ext.Date#format} to format a Date for this Column that is within this year.
     *
     * Default to "F j, H:i" e.g May 1, 11:14
     */

    /**
     * @cfg {String} yearFormat
     * A formatting string as used by {@link Ext.Date#format} to format a Date for this Column that is outside of this year.
     *
     * Default to "F j, y" e.g May 1, 2010
     */

    initComponent: function(){
        if (!this.recentFormat) {
            this.recentFormat = 'H:i:s';
        }

        if (!this.weekFormat) {
            this.weekFormat = 'l, H:i';
        }

        if (!this.monthFormat) {
            this.monthFormat = 'F j, H:i';
        }

        if (!this.yearFormat) {
            this.yearFormat = 'F j, y';
        }

        this.callParent(arguments);
    },

    renderer: function(value, metaData){
        var column = metaData.column;
        var now = new Date();
        var today = new Date(now.getFullYear(), now.getMonth(), now.getDay());
        var startOfThisWeek = new Date(today.getTime() - (60*60*24*6));
        var startOfThisYear = new Date(now.getFullYear(), 0, 1);
        var endOfThisYear = new Date(now.getFullYear(), 11, 31, 23, 59, 59);
        var endOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59);
        var yesterday = new Date(now.getFullYear(), now.getMonth(), now.getDate()-1);
        var tomorrow = new Date(now.getFullYear(), now.getMonth(), now.getDate()+1, 23, 59, 59);
        if (Ext.Date.between(value, yesterday, tomorrow)) {
            var day;
            if (Ext.Date.between(value, today, endOfToday)) day = '';
            else if (value > endOfToday) day = 'Tomorrow, ';
            else day = 'Yesterday, ';
            return day + Ext.Date.format(value, column.recentFormat);
        } else if (value < now) {
            if (value >= startOfThisWeek) {
                return Ext.Date.format(value, column.weekFormat);
            } else if (value >= startOfThisYear) {
                return Ext.Date.format(value, column.monthFormat);
            }
        } else if (value > now) {
            if (value < today.getTime() + (60*60*24*6)) {
                return 'Coming ' + Ext.Date.format(value, column.weekFormat);
            } else if (value < endOfThisYear) {
                return Ext.Date.format(value, column.monthFormat);
            }
        }
        return Ext.Date.format(value, column.yearFormat);
    }
});