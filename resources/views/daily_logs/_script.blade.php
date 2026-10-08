<script>
    function panenForm(init) {
        return Object.assign({
            num(v) { return Number(v) || 0; },
            gradeEggs(i) { var g = this.grades[i]; return this.num(g.trays) * 30 + this.num(g.extra); },
            totalEggs() { var t = 0; for (var i = 0; i < this.grades.length; i++) t += this.gradeEggs(i); return t; },
            totalKg() { var t = 0; this.grades.forEach(g => t += this.num(g.kg)); return t; },
            feedKg() { return this.num(this.sacks) * this.sackKg + this.num(this.extraFeed); },
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
                if (this.lastFeed[this.coopId]) this.feedId = String(this.lastFeed[this.coopId]);
            },
            step(ref, d) {
                var el = this.$refs[ref];
                el.value = Math.max(0, (Number(el.value) || 0) + d);
            },
        }, init);
    }
</script>
