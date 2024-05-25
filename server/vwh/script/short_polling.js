let update_id = 0;

function short_polling(interval_in_seconds, for_page, callback_with_JSON_parsed_data) {

	const short_poll = {
		interval: interval_in_seconds * 1000,

		id: undefined,
		request: undefined,

		init: function () {
			short_poll.request = new XMLHttpRequest();

			short_poll.request.onreadystatechange = function () {
				if (short_poll.request.readyState === XMLHttpRequest.DONE) {
					if (short_poll.request.status === 200) {
						let up_to_date = short_poll.request.responseText.trim() === '1' ? true : false;

						if (up_to_date) {
							short_poll.start();
						}
						else { // not up to date
							short_poll.retrieve_data();
						}
					}
					else {
						console.error('Short polling aborted, status received:', short_poll.request.status);
					}
				}
			};

			short_poll.do(); // trigger for first time to start polling cycle
		},

		start: function () {
			short_poll.id = setInterval(short_poll.do, short_poll.interval);
		},

		stop: function () {
			clearInterval(short_poll.id);
		},

		do: function () {
			short_poll.stop();

			short_poll.request.open('GET', '/_update.php?am_i_up_to_date=' + update_id, true);
			short_poll.request.send();
		},

		retrieve_data: function () {
			let r = new XMLHttpRequest();
			r.onreadystatechange = function () {
				if (r.readyState === XMLHttpRequest.DONE) {
					if (r.status === 200) {
						parsed_data = JSON.parse(r.responseText);
						callback_with_JSON_parsed_data(parsed_data);
						update_id = parsed_data['update']; // keep after, so form submissions sync with UI etc
						// TODO maybe do something with a "mutex"?
					}
					else {
						console.error('Data retrieval failed with status:', r.status);
					}
	
					short_poll.start();
				}
			};
			r.open('GET', '/_update.php?get_me_up_to_date=' + for_page, true);
			r.send();
		}
	};

	short_poll.init();
}
