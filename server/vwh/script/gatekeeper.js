const dialog = document.getElementById('dialog_action');
	const form = document.getElementById("dialog_action_form");
		const form_input_interview_id = document.getElementById("input_interview_id");
		const form_input_interviewer_id = document.getElementById("input_interviewer_id");

		const form_button_to_happening = document.getElementById("button_to_happening");
		const form_button_to_completed = document.getElementById("button_to_completed");
		const form_button_to_dequeue = document.getElementById("button_to_dequeue");
		const form_button_active_inactive = document.getElementById("button_active_inactive");
		const form_button_cancel = document.getElementById("button_cancel");

const container_interviewers = document.getElementById("container_interviewers");

let calling_time_in_seconds = 0;

const interviewers = {};

class Interview {

	#id;
	#interviewee_id;
	#interviewer_id;
	#state;
	#state_timestamp;

	constructor (row) {
		this.#id = row['id'];
		this.#interviewee_id = row['id_interviewee'];
		this.#interviewer_id = row['id_interviewer'];
		this.#state = row['state_'];
		this.#state_timestamp = Date.parse(row['state_timestamp'] + '+00:00');
	}

	getId() { 
		return this.#id;
	}

	getIntervieweeId() { 
		return this.#interviewee_id;
	}

	getInterviewerId() { 
		return this.#interviewer_id;
	}

	getState() { 
		return this.#state;
	}

	getStateTimestamp() { 
		return this.#state_timestamp;
	}

}

class Interviewer {

	static #noInterview = new Interview({});

	static isNoInterview(interview) {
		return this.#noInterview === interview;
	}

	#id = undefined;
	#name = undefined;
	#table = undefined;
	#image_url = undefined;
	#active = undefined;
	
	#interview;
	
	#observers = [];

	constructor(row) {
		this.#id = row['id'];
		this.update(row);
		this.updateInterview();
	}

	/**
	 * Has to be manually called. Dereferences all outgoing references.
	 * @returns previously added observers
	 */
	destructor() {
		this.#id = undefined;
		this.#name = undefined;
		this.#table = undefined;
		this.#image_url = undefined;
		this.#active = undefined;

		this.#interview = undefined;
		
		let observers = this.#observers;
		this.#observers = undefined;

		return observers;
	}

	// ===

	observerAdd(obs) {
		if(typeof(obs.notify) !== 'function') {
			throw new TypeError('Expecting \'notify\' function on the \'obs\' parameter')
		}

		this.#observers.push(obs);
		obs.notify(this);
		obs.notify(this.#interview);
	}

	observerRemove(obs) {
		let index = this.#observers.indexOf(obs);
		if(index > -1) {
			this.#observers.splice(index, 1);
		}
	}

	#observersNotify(data) {
		this.#observers.forEach((obs) => {
			if(typeof(obs.notify) === 'function') {
				obs.notify(data);
			}
		})
	}

	// ===

	update(row) {
		this.#name = row['name'];
		this.#table = row['table_number'] === '' ? '-' : row['table_number'];
		this.#image_url = row['image_resource_url'];
		this.#active = row['active'];

		this.#observersNotify(this);
	}

	updateInterview(interview = undefined) {
		if(interview === undefined) {
			this.#interview = Interviewer.#noInterview;
		}
		else {
			if(interview instanceof Interview === false) {
				throw new TypeError('Parameter must of be object of class \'Interview\'');
			}

			this.#interview = interview;
		}

		this.#observersNotify(this.#interview);
	}

	// ===

	getId() {
		return this.#id;
	}

	getName() {
		return this.#name;
	}

	getTable() {
		return this.#table;
	}

	getImageUrl() {
		return this.#image_url;
	}

	getActive() {
		return this.#active;
	}
	
	getInterview() {
		return this.#interview;
	}

}

class InterviewerElement {

	#container;
		#info_container;
			#info_img;
			#info_p;
		#status_indicator;
		#status_information;

	#interviewer;

	#live_time_counter_interval_id;

	constructor() {
		let e = this.#container = document.createElement('div');
		e.classList.add('interviewer');
		
		e = this.#info_container = document.createElement('div');
		e.classList.add('info');
		e = this.#info_img = document.createElement('img');
		e.classList.add('image');
		e = this.#info_p = document.createElement('p');
		e.classList.add('text');

		this.#info_container.append(
			this.#info_img,
			this.#info_p
		);
		
		e = this.#status_indicator = document.createElement('div');
		e.classList.add('status_indicator');
		e = this.#status_information = document.createElement('p');
		e.classList.add('status_information');
		
		this.#container.append(
			this.#info_container,
			this.#status_indicator,
			this.#status_information
		);
	}

	get() {
		return this.#container;
	}

	// ===

	notify(data) {
		if(data instanceof Interviewer === true) {
			let iwer = this.#interviewer = data;
	
			this.#info_img.src = iwer.getImageUrl();
			this.#info_p.innerHTML =
				iwer.getName() + " " +
				"<br>Table: " + iwer.getTable();
		}
		else if(data instanceof Interview === true) {
			clearInterval(this.#live_time_counter_interval_id);
			
			let iw = data;

			if(Interviewer.isNoInterview(iw)) {
				if(this.#interviewer.getActive() === true) {
					this.#status_information.innerHTML =
						Math.random() < 0.97 ? "Available" : "**cricket noises**";
					this.#status_indicator.classList.add('status_indicator--available');
				}
				else {
					this.#status_information.innerHTML = "Paused";
					this.#status_indicator.classList.add('status_indicator--paused');
				}
			}
			else {
				if(iw.getState() === 'DECISION') {
					this.#status_indicator.classList.add('status_indicator--decision');
					this.#status_information.innerHTML =
						'Decision for Interviewee ' +
						iw.getIntervieweeId()
						;
				}
				else {
					let f = () => {
						switch (iw.getState()) {
							case 'CALLING':
								let remaining = iw.getStateTimestamp() + (calling_time_in_seconds * 1000) - Date.now();

								remaining = new Date(remaining > 0 ? remaining : 0);
			
								remaining = (remaining.getUTCMinutes() < 10 ? '0' : '') + remaining.getUTCMinutes() + ":"
									+ (remaining.getUTCSeconds() < 10 ? '0' : '') + remaining.getUTCSeconds();

								// ---

								this.#status_indicator.classList.add('status_indicator--calling');
								this.#status_information.innerHTML = 
									'Calling Interviewee ' +
									iw.getIntervieweeId() +
									'<br>Remaining: <span>' +
									remaining +
									'</span>';
								break;
							case 'HAPPENING':
								let elapsed = Date.now() - iw.getStateTimestamp();

								elapsed = new Date(elapsed);
			
								elapsed = (elapsed.getUTCHours() < 10 ? '0' : '') + elapsed.getUTCHours() + ":" 
									+ (elapsed.getUTCMinutes() < 10 ? '0' : '') + elapsed.getUTCMinutes() + ":"
									+ (elapsed.getUTCSeconds() < 10 ? '0' : '') + elapsed.getUTCSeconds();

								// ---

								this.#status_indicator.classList.add('status_indicator--happening');
								this.#status_information.innerHTML = 
									'Happening with Interviewee ' +
									iw.getIntervieweeId() +
									'<br>Elapsed: <span>' +
									elapsed +
									'</span>';
								break;

							default: /* should not come here */ return;
						}
					};

					f();

					this.#live_time_counter_interval_id = setInterval(f, 500);
				}

			}

			if(this.#status_indicator.classList.length === 3) { // (0) base + (1) old + (2) new
				this.#status_indicator.classList.remove(
					this.#status_indicator.classList.item(1)
				);
			}
		}
		else {
			console.log("Haha???");
		}
	}

}

form.addEventListener("submit", (event) => {

	if(event.submitter === form_button_cancel) {
		form.reset();
		return;
	}

	let confirm_message = (() => {
		let iwer = interviewers[form_input_interviewer_id.value];
		let iwee_id = iwer.getInterview().getIntervieweeId();

		switch (event.submitter) {
			case form_button_to_happening:
				return "moving interviewer '"
					+ iwer.getName() + "' to interview happening with interviewee '"
					+ iwee_id + "'";

			case form_button_to_completed:
				return "completing interview with interviewer '"
					+ iwer.getName() + "' and interviewee '"
					+ iwee_id + "'";

			case form_button_to_dequeue:
				return "removing this interview. Interviewee " +
					iwee_id + " will be able to enqueue again via the Secretary at Interviewer '" +
					iwer.getName() + "'";

			case form_button_active_inactive:
				$s = '';

				if(iwer.getActive() === true) {
					$s = "pausing Interviewer '" + iwer.getName() + "'.";
					
					if(Interviewer.isNoInterview(iwer.getInterview()) === false) {
						$s += " Interviewee "  + iwee_id +
						" will stay ENQUEUED (like it never started CALLING) on the Interviewer.'";
					}
				}
				else {
					$s = "unpausing Interviewer '" + iwer.getName() + "'.";
				}

				return $s;
		}

		return null;
	})();

	submiting(form, confirm_message, () => {
		dialog.close();
		form.reset();
	}, event);
});

// ===

function interviewer_element_dialog_form_preperation(iwer) {
	form_input_interviewer_id.value = iwer.getId();
		
	form_button_active_inactive.innerText =
		iwer.getActive() === true ? 'Pause' : 'Unpause';
		
	display(false, [
		form_button_to_dequeue,
		form_button_to_happening,
		form_button_to_completed
	]);
		
	let iw = iwer.getInterview();
		
	if (Interviewer.isNoInterview(iw) === false) {
		form_input_interview_id.value = iw.getId();
		
		let iw_state = iw.getState();
		
		if (iw_state === 'CALLING' || iw_state === 'DECISION') {
			display(true, [
				form_button_to_dequeue,
				form_button_to_happening
			]);
		}
		else if (iw_state === 'HAPPENING') {
			display(true, [
				form_button_to_dequeue,
				form_button_to_completed
			]);
		}
		else {
			// ??? should not be here in the first place
		}
	}
}

function update(data) {
	calling_time_in_seconds = data['calling_time'];

	update_interviewers(data['interviewers']);
	// update_interviewees(data['interviewees']); // non utilized
	update_interviews(data['interviews']);

	if(interviewers[form_input_interviewer_id.value] !== undefined) {
		interviewer_element_dialog_form_preperation(
			interviewers[form_input_interviewer_id.value]
		);
	}
	else {
		dialog.close();
		form.reset();
	}

	display(
		container_interviewers.childElementCount === 1, // TODO eh lazy solution
		[document.getElementById('no_interviewers_message')]
	);
}

function update_interviewers(rows) {
	let interviewer_ids_to_delete = Object.keys(interviewers);

	rows.forEach((row) => {

		let interviewer =  interviewers[row['id']];

		if(interviewer === undefined) {
			interviewer = interviewers[row['id']] =
				new Interviewer(row);
			
			ie = new InterviewerElement();
			interviewer.observerAdd(ie);
			ie.get().addEventListener('click', (event) => {
				interviewer_element_dialog_form_preperation(interviewer);
				dialog.showModal();
			});

			container_interviewers.appendChild(ie.get());
		}
		else {
			interviewer.update(row);

			interviewer_ids_to_delete.splice(interviewer_ids_to_delete.indexOf(interviewer.getId().toString()), 1);
		}
	});

	interviewer_ids_to_delete.forEach((id) => {
		let elements = interviewers[id].destructor();
		delete interviewers[id];
		
		elements.forEach((e) => {
			if(e instanceof InterviewerElement === true) {
				e.get().parentElement.removeChild(e.get());
			}
		});
	});
}

function update_interviews(rows) {
	let interviewer_ids_without_interview = Object.keys(interviewers);

	rows.forEach(row => {
		let iwer = interviewers[row['id_interviewer']];

		iwer.updateInterview(new Interview(row));
		interviewer_ids_without_interview.splice(interviewer_ids_without_interview.indexOf(iwer.getId().toString()), 1);
	});

	interviewer_ids_without_interview.forEach((id) => {
		interviewers[id].updateInterview(); // default undefined
	});
}
