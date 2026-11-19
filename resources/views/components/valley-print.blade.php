<svg class="valley-print" viewBox="0 0 640 320" preserveAspectRatio="xMidYMax slice" aria-hidden="true" focusable="false">
    <defs>
        <pattern id="valley-paper" width="5" height="5" patternUnits="userSpaceOnUse">
            <circle cx="1" cy="1" r=".65" fill="currentColor" opacity=".3"/>
        </pattern>
        <pattern id="valley-hatch" width="6" height="6" patternUnits="userSpaceOnUse" patternTransform="rotate(30)">
            <path d="M0 0V6" stroke="currentColor" opacity=".28"/>
        </pattern>
    </defs>
    <path fill="var(--print-sky)" d="M0 0h640v320H0z"/>
    <path fill="url(#valley-paper)" d="M0 0h640v320H0z"/>
    <circle cx="400" cy="98" r="54" fill="var(--print-sun)"/>
    <circle cx="400" cy="98" r="63" fill="none" stroke="currentColor" opacity=".28"/>
    <path fill="var(--print-far)" d="M0 202Q88 185 177 104T359 129Q399 165 463 118T640 74V320H0Z"/>
    <path fill="url(#valley-hatch)" d="M0 202Q88 185 177 104T359 129Q399 165 463 118T640 74V320H0Z"/>
    <path fill="var(--print-mid)" d="M0 166Q97 116 208 203T423 178Q539 93 640 139V320H0Z"/>
    <path fill="var(--print-ink)" d="M0 234Q108 150 241 217T480 221Q570 167 640 162V320H0Z"/>
    <g fill="none" stroke="var(--print-far)" stroke-width="1.2" opacity=".68">
        @for($line = 0; $line < 19; $line++)
            <path transform="translate(0 {{ $line * 6 }})" d="M-12 250Q103 164 237 229T480 234Q572 178 652 174"/>
        @endfor
    </g>
</svg>
