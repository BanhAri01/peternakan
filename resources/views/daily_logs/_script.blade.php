<script>
    function panenForm(init) {
        var seq = 0;
        init.feeds = (init.feeds || []).map(function (f) { return { key: ++seq, id: String(f.id || ''), sacks: f.sacks ?? '', extra: f.extra ?? '' }; });
        if (!init.feeds.length) init.feeds = [{ key: ++seq, id: String(init.feedIds[0] || ''), sacks: '', extra: '' }];
        return Object.assign({
            num(v) { return Number(v) || 0; },
            gradeEggs(i) { var g = this.grades[i]; return this.num(g.trays) * 30 + this.num(g.extra); },
            totalEggs() { var t = 0; for (var i = 0; i < this.grades.length; i++) t += this.gradeEggs(i); return t; },
            totalKg() { var t = 0; this.grades.forEach(g => t += this.num(g.kg)); return t; },
            lineKg(i) { var f = this.feeds[i]; return this.num(f.sacks) * this.sackKg + this.num(f.extra); },
            feedKg() { var t = 0; for (var i = 0; i < this.feeds.length; i++) t += this.lineKg(i); return t; },
            isFeedUsed(id, i) { return this.feeds.some(function (f, j) { return j !== i && f.id === String(id); }); },
            addFeed() {
                var used = this.feeds.map(function (f) { return f.id; });
                var next = this.feedIds.map(String).filter(function (id) { return used.indexOf(id) === -1; })[0];
                if (next) this.feeds.push({ key: ++seq, id: next, sacks: '', extra: '' });
            },
            removeFeed(i) { if (this.feeds.length > 1) this.feeds.splice(i, 1); },
            trayText() {
                var e = this.totalEggs(), r = Math.floor(e / 30), s = e % 30;
                return r + ' rak' + (s ? ' + ' + s : '');
            },
            hdp() {
                var pop = this.num(this.populations[this.coopId]);
                if (!pop || !this.totalEggs()) return '-';
                return angka(this.totalEggs() / pop * 100, 1) + '%';
            },
            onCoopChange() {
                var last = this.lastFeed[this.coopId];
                if (last && this.feeds.length === 1 && !this.isFeedUsed(last, 0)) this.feeds[0].id = String(last);
            },
            step(ref, d) {
                var el = this.$refs[ref];
                el.value = Math.max(0, (Number(el.value) || 0) + d);
            },
        }, init);
    }
</script>
