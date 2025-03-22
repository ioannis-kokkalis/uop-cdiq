<?php header("Content-type: text/css"); ?>

/* Notes: 
mobile first with small fixes for larger screens
*/

@import url("/style/font.css");

@import url("/style/color.css?cachebuster=<?= date("YmdH") ?>");

::selection {
	background-color: var(--color-primary);
	color: var(--color-white);
}

/* --- */

*, *::before, *::after {
	box-sizing: border-box;
	font-family: local-font-family;
}

body, header, footer, main, nav, hr, p {
	margin: 0;
}

/* --- */

html {
	word-break: break-word;
	
	background-color: var(--color-white);
}

body {
	display: flex;
	flex-direction: column;
	align-items: center;

	height: auto;
	min-height: 100dvh;
	width: 100%;

	padding: 1rem;
}

hr {
	margin: .5rem 0;
	border: none;
}

a,
a:visited {
	text-decoration: none;
	
	color: var(--color-accent);

	font-weight: bold;
}

a:hover {
	text-decoration: underline;
}

button {
	padding: .5rem;
}

header, footer, main, nav, hr {
	width: 100%;
}

header, footer {
	display: flex;
	flex-direction: column;
	align-items: center;
}

.header_title_container {
	position: relative;

	& > .polygon {
		clip-path: polygon(5% 15%, 100% 0%, 90% 100%, 0% 80%);
		
		position: absolute;
		
		width: 100%;
		height: 100%;
		
		--animation-duration: 5s;
		
		animation-name: little_rotation;
		animation-iteration-count: infinite;
		animation-duration: var(--animation-duration);
		animation-timing-function: ease;
	}

	& > .polygon_primary {
		background-color: var(--color-primary);
	}

	& > .polygon_accent {
		transform: rotateY(180deg);
		
		animation-delay: calc(var(--animation-duration) * .5);
		
		background-color: var(--color-accent);
	}
	
	& > .text {
		position: relative;

		text-align: center;
		
		padding: 2rem;
		margin: 0;
		
		color: var(--color-white);

		&::selection {
			background-color: var(--color-white);
			color: var(--color-primary);
		}
	}
}

@keyframes little_rotation {
	50% {
		rotate: 2.5deg;
    }
}

nav {
	display: flex;
	justify-content: center;
	
	flex-wrap: wrap;
}

nav a {
	padding: .5rem;
}

main {
	display: flex;
	flex-direction: column;
	
	align-items: center;
	justify-content: center;
	
	gap: 1rem;

	padding: .75rem 0px;
}

form {
	display: flex;
	flex-direction: column;
	gap: 1rem;

	width: 100%;
}

form fieldset {
	display: flex;
	flex-direction: column;
	
	gap: 1rem;
	padding: 1.25rem;
	padding-top: .75rem;
}

form input,
form button,
form select,
form input::file-selector-button
{
	padding: .4rem .8rem;
}

form textarea {
	padding: .5rem;
}

form input[type="file"] {
	width: 60dvw;
	max-width: min-content;
	padding: 0;
}

form select {
	width: 100%;
}

.form-jobpositions {

	.info {
		text-align: center;
		margin: 0;
	}

	& input[type="text"],
	& textarea {
		padding: .5rem;
	}

}

.horizontal_buttons {
	display: flex;
	flex-direction: row;
	justify-content: flex-end;

	gap: .75rem;
}

dialog[open] {
	left: 2rem;
	right: 2rem;

	display: flex;
	flex-direction: column;

	gap: .75rem;

    border: 1px solid #5f5f5f;

    padding: 1.2rem;
}

dialog::backdrop {
	background-color: rgba(0, 0, 0, 0.4);
}

dialog p {
	text-align: center;
}

dialog button {
	padding: .4rem .8rem;
}

#iwer_info_dialog[open] {
	left: 1rem;
	right: 1rem;

	width: auto;

	& > form > label { /* TODO update other CSS rules to utilize "&" nesting */
		display: flex;
		flex-direction: column;
	
		gap: .25rem;
	}
}

#dialog_action[open] {

	& hr {
		margin: .25rem 0;
	}
}

/* --- */

h1, h2, h3, h4, h5, h6 {
	line-height: 2.5rem;
	margin: 0;
}

/* --- */

.spacer {
	margin: auto;
}

#iwer_checkboxes {
	display: flex;
	flex-direction: column;
	align-items: stretch;

	border: 1px solid #5f5f5f;

	& > label {
		display: flex;
		flex-direction: row;
		align-items: center;
	
		flex-grow: 1;
	
		gap: .6rem;
	
		padding: 1rem .5rem;

		&:nth-child(odd) {
			background-color: #fcfcfc;
		}

		&:nth-child(even) {
			background-color: #ffffff;
		}

		&:has(> input[style="display: none;"]) {
			padding: 1rem;
		}

		& > img {
			aspect-ratio: 1 / 1;
			object-fit: cover;
			width: clamp(0px, 15dvw, 96px);
		}
	}
}

.container_interviewers, .dialog_details[open] {
	display: flex;
	flex-direction: row;
	flex-wrap: wrap;
	justify-content: center;
	align-items: stretch;

	width: 100%;

	gap: 1rem;

	& > .interviewer {
		display: flex;
		flex-direction: column;

		width: 100%;

		border: 1px solid #5f5f5f;

		& > .info {
			display: flex;
			flex-direction: row;
			align-items: center;
	
			gap: .6rem;
		
			padding: 1rem;

			& > .image {
				aspect-ratio: 1 / 1;
				object-fit: cover;
				width: clamp(0px, 15dvw, 96px);
			}

			& > .text {
				line-height: 1.5rem;
			}
		}

		& > .status_indicator {
			width: 100%;
			height: 1rem;
			background-color: var(--color-black);
		}

		& > .status_indicator--available {
			background-color: var(--color-status--available);
		}

		& > .status_indicator--calling {
			background-color: var(--color-status--calling);
		}

		& > .status_indicator--decision {
			background-color: var(--color-status--decision);
		}

		& > .status_indicator--happening {
			background-color: var(--color-status--happening);
		}
		
		& > .status_indicator--paused {
			background-color: var(--color-status--unavailable);
		}
	
		& > .status_information {
			flex-grow: 1;

			text-align: center;
			line-height: 1.25rem;
			
			padding: 0.5rem;

			& > span {
				font-family: Consolas;
			}
		}
	}

	& > .interviewer:hover {
		cursor: pointer;
	}
}

.dialog_details[open] {
	flex-direction: column;
	align-items: stretch;
	justify-content: flex-start;
	flex-wrap: nowrap;

	width: auto;

	outline: none;

	& p {
		text-align: left;
	}

	& > .interviewer:hover {
		cursor: default;
	}

	& > .quueueue {
		display: flex;
		flex-direction: column;
		
		& > .title_with_count {
			display: flex;
			flex-direction: row;

			& > .count {
				margin-left: auto;
			}
		}

		& > .horizontal_scrollable {
			display: flex;
			flex-direction: row;
			flex-wrap: nowrap;

			overflow-y: hidden;
			overflow-x: auto;
			
			gap: 1.5rem;
	
			& > .interviewee {
				text-wrap: nowrap;

				font-size: 2rem;
				font-weight: 900;
			}
			
			& > .interviewee--unavailable {
				color: var(--color-status--unavailable);
			}
			
			& > .interviewee--available {
				color: var(--color-status--available);
			}
	
			& > .interviewee--calling {
				color: var(--color-status--calling);
			}
			
			& > .interviewee--decision {
				color: var(--color-status--decision);
			}
			
			& > .interviewee--happening {
				color: var(--color-status--happening);
			}
	
			& > .interviewee--completed {
				color: var(--color-status--completed);
			}
		}
	}
}

#suggestions-main {

	& p,
	& h1, 
	& h2,
	& h3 {
		text-align: center;
	}

	& button {
		padding: .5rem;
	}

	& #current_url_qr {
		aspect-ratio: 1 / 1;
		object-fit: cover;
		width: clamp(0px, 75vw, 256px);
	}

	& > .interviewer {
		display: flex;
		flex-direction: column;
		align-items: center;

		gap: .5rem;
	}
}

#index-main {
	text-align: center;

	& #current_url_qr {
		aspect-ratio: 1 / 1;
		object-fit: cover;
		width: clamp(0px, 75vw, 256px);
	}
}

.info-dialog {
	& p {
		text-align: left;
	}

	& ul {
		display: flex;
		flex-direction: column;
		gap: .5rem;

		margin: 0;
	}

	& .av {
		color: var(--color-white);
		background-color: var(--color-status--available);
	}

	& .ca {
		color: var(--color-white);
		background-color: var(--color-status--calling);
	}

	& .de {
		color: var(--color-white);
		background-color: var(--color-status--decision);
	}

	& .ha {
		color: var(--color-white);
		background-color: var(--color-status--happening);
	}

	& .pa {
		color: var(--color-white);
		background-color: var(--color-status--unavailable);
	}
}

@media screen and (min-width: 501px) {
	main {
		max-width: 650px;
	}
	
	dialog {
		max-width: 450px;
	}
}

@media screen and (min-width: calc(280px * 2 + 2.5rem)) {
	main:has(.container_interviewers) {
		max-width: 100%;
		
		& > .container_interviewers > .interviewer {
			max-width: 280px;
		}
	}
}
