<div
    x-data="{
        share() {
            const url = '{{ url()->current() }}';
            @if(isset($project))
                const title = @js($project->title);
            @elseif(isset($artist))
                const title = @js($artist->name);
            @endif

            if (navigator.share) {
                navigator.share({ title, url }).catch(() => {});
            } else if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(() => {
                    alert('Link copied to clipboard');
                });
            } else {
                alert(url);
            }
        }
    }"
    class="flex justify-end items-center"
>
    <button
        type="button"
        @click="share"
        class="inline-flex justify-center items-center rounded-full bg-slate-700 p-1.5 hover:text-orange-500 hover:bg-white transition-colors duration-200"
    >
        <x-heroicon-o-arrow-up-on-square class="w-6 h-6 pb-0.5 stroke-1.5" />
        <span class="sr-only">Share this page</span>
    </button>
</div>
