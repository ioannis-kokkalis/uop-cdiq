<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/.private/assembler.php';

$a = new Assembler('CNDIQ 2025');

$a->body_main_id = 'index-main';

$a->body_main = function() { ?>
	<h1>What is this place?</h1>

	<p>Here you can stay up to date with all interviews during <a href="https://careerday.fet.uop.gr/">Career &#38; Networking Day 2025</a>. This includes currently happening interviews, interviews in each company's queue, and their related states at any moment.</p>
	<p>Furthermore, we can suggest to you positions and companies that fit you best based on your resume.</p>
	<h6 style="line-height: 1rem;">Navigate from the menu at the top!</h6>

	<h1>Interview where?</h1>

	<p>Book your interviews at the <strong>Secretary</strong>, then pay attention on the interview updates. If any is for you, head to the <strong>Gatekeeper</strong> to guide you!</p>
	<h6 style="line-height: 1rem;">Your priority on interview queues is determined by the time you booked your interview.</h6>

	<h1>Share</h1>
	
	<p>Use the QR code...</p>
	<script src="/script/utilities.js"></script>
	<img id="current_url_qr" alt="...it did not generated properly...">
	<script>qr_generate(window.location.href, document.getElementById('current_url_qr'));</script>
	<p>...or <a target="_self" onclick="copy_to_clipboard(window.location.href)">copy the link</a> and send it!</p>
<?php };

$a->assemble();
