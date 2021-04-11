Vue.filter('formatDateTime', 
    function (value) {
        if (!value) {
            return ''
        }
        value = new Date(value);
        return value.toLocaleDateString() + " " + value.toLocaleTimeString();
    }
);

Vue.filter('formatDate', 
    function (value) {
        if (!value) {
            return ''
        }
        value = new Date(value);
        return value.toLocaleDateString();
    }
);

Vue.filter('formatDateTimeRange', 
    function (dateFrom, dateTo) {
        let from = new Date(dateFrom);
        let to = new Date(dateTo);
        let output = from.toLocaleDateString() + " " 
                + from.toLocaleTimeString() + " to ";
        if ( to.toLocaleDateString() != from.toLocaleDateString())  {
            output += to.toLocaleDateString() + " ";
        }
        output += to.toLocaleTimeString();
        return output;
    }
);

//From https://stackoverflow.com/a/35071765/1213708
Vue.filter('truncate', 
    function (value, stop) {
        // If null, set to empty
        value = value || '';
        return value.slice(0, stop) + (stop < value.length ? '...' : '')
    }
);

function formatTo2Digits( input )  {
    return input.toLocaleString('en-US', {minimumIntegerDigits: 2, useGrouping:false})
}

Vue.filter('dateDiff',
    function ( milliseconds ) {
        let offsetBefore = milliseconds < 0;
        milliseconds = Math.abs(milliseconds);
        let seconds = Math.floor(milliseconds / 1000);
        let minute = Math.floor(seconds / 60);
        seconds = seconds % 60;
        let hour = Math.floor(minute / 60);
        minute = minute % 60;
        let day = Math.floor(hour / 24);
        hour = hour % 24;
        let rep = day + " days " + hour 
            + ":" + formatTo2Digits(minute)
            + ":" + formatTo2Digits(seconds);
        if ( offsetBefore ) {
            rep += " (earlier)";
        }
        return rep;
})
