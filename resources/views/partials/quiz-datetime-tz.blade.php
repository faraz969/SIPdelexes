{{-- Convert datetime-local between browser local time and app timezone --}}
<script>
(function () {
    var appTz = @json(config('app.timezone'));

    function partsInTz(date, timeZone) {
        var fmt = new Intl.DateTimeFormat('en-US', {
            timeZone: timeZone,
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        });
        var bag = {};
        fmt.formatToParts(date).forEach(function (p) {
            if (p.type !== 'literal') bag[p.type] = p.value;
        });
        if (bag.hour === '24') bag.hour = '00';
        return bag;
    }

    function formatYmdHm(bag) {
        return bag.year + '-' + bag.month + '-' + bag.day + 'T' + bag.hour + ':' + bag.minute;
    }

    /** App wall-clock "YYYY-MM-DDTHH:mm" → browser-local datetime-local value */
    function appWallToLocalInput(value) {
        if (!value) return '';
        var bits = value.split('T');
        if (bits.length < 2) return value;
        var ymd = bits[0].split('-').map(Number);
        var hm = bits[1].split(':').map(Number);
        var y = ymd[0], m = ymd[1], d = ymd[2], hh = hm[0], mm = hm[1] || 0;
        var targetAsUtc = Date.UTC(y, m - 1, d, hh, mm, 0);
        var utcGuess = targetAsUtc;
        for (var i = 0; i < 3; i++) {
            var bag = partsInTz(new Date(utcGuess), appTz);
            var asUtc = Date.UTC(+bag.year, +bag.month - 1, +bag.day, +bag.hour, +bag.minute, 0);
            utcGuess += targetAsUtc - asUtc;
        }
        var local = new Date(utcGuess);
        function pad(n) { return String(n).padStart(2, '0'); }
        return local.getFullYear() + '-' + pad(local.getMonth() + 1) + '-' + pad(local.getDate())
            + 'T' + pad(local.getHours()) + ':' + pad(local.getMinutes());
    }

    /** Browser-local datetime-local → app wall-clock */
    function localInputToAppWall(value) {
        if (!value) return '';
        var instant = new Date(value);
        if (isNaN(instant.getTime())) return value;
        return formatYmdHm(partsInTz(instant, appTz));
    }

    document.querySelectorAll('input[data-app-datetime]').forEach(function (input) {
        if (input.dataset.tzReady) return;
        if (input.value) {
            input.value = appWallToLocalInput(input.value);
        }
        input.dataset.tzReady = '1';
    });

    document.querySelectorAll('form[data-convert-quiz-datetimes]').forEach(function (form) {
        if (form.dataset.tzBound) return;
        form.dataset.tzBound = '1';
        form.addEventListener('submit', function () {
            form.querySelectorAll('input[data-app-datetime]').forEach(function (input) {
                input.value = localInputToAppWall(input.value);
            });
        });
    });
})();
</script>
