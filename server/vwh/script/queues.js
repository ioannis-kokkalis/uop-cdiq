
const no_interviewers_message = document.getElementById('no_interviewers_message');
const container_interviewers = document.getElementById("container_interviewers");

let calling_time_in_seconds = 0;

// ===

class Notifier {

	#observers

	constructor() {
        this.#observers = [];
    }

	observerAdd(obs) {
		if(obs instanceof Observer === false) {
			throw new TypeError("Parameter 'obs' must be instance of 'Observer'");
		}

		this.#observers.push(obs);

		obs.observe(this);
	}

	observerRemove(obs) {
		let index = this.#observers.indexOf(obs);
		if(index > -1) {
			this.#observers.splice(index, 1);
		}
	}

	observerRemoveAll() {
		let observers = this.#observers;
		this.#observers = [];
		return observers;
	}

	observerNotify(data) {
		this.#observers.forEach((obs) => obs.observe(data));
	}

}

class Observer {

    observe(data) {
        throw new Error("You have to implement the method 'observe'!");
    }

}

// ---

class Interviewer extends Notifier {

	#id = undefined;
	#name = undefined;
	#table = undefined;
	#image_url = undefined;
	#active = undefined;

	#interviews = [];
	#interviews_completed = [];
	#interview_current = undefined;

	constructor(row) {
		super();
		
		this.#id = row['id'];
		this.update(row);
	}

	update(row) {
		this.#name = row['name'];
		this.#table = row['table_number'] === '' ? '-' : row['table_number'];
		this.#image_url = row['image_resource_url'];
		this.#active = row['active'];

		this.observerNotify(this);
	}

	updateInterviews(all, current = undefined) {
		let updated_interviews = [];
		let updated_interviews_completed = [];

		all.forEach((iw) => {
			if(iw.getState() === 'COMPLETED') {
				updated_interviews_completed.push(iw);
			}
			else {
				updated_interviews.push(iw);
			}
		});

		this.#interviews = updated_interviews;
		this.#interviews_completed = updated_interviews_completed;
		this.#interview_current = current;

		this.observerNotify({notifier: this, reason: 'interviews'});
	}

	// ===

	observerAdd(obs) {
		super.observerAdd(obs);

		obs.observe({notifier: this, reason: 'interviews'}); // account for interviews
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

	getInterviews() {
		return Array.from(this.#interviews);
	}

	getInterviewsCompleted() {
		return Array.from(this.#interviews_completed);
	}

	getInterviewCurrent() {
		return this.#interview_current;
	}
	
}

class Interviewee {

	#id = undefined;
	#available = undefined;

	constructor(row) {
		this.#id = row['id'];

		this.update(row);
	}

	update(row) {
		this.#available = row['available'];
	}

	// ===

	getId() {
		return this.#id;
	}

	getAvailable() {
		return this.#available;
	}

}

class Interview {

	#id;
	#interviewee;
	#interviewer;
	#state;
	#state_timestamp;

	constructor (row) {
		this.#id = row['id'];

		this.update(row);
	}

	update(row) {
		this.#interviewee = row['interviewee'];
		this.#interviewer = row['interviewer'];
		this.#state = row['state_'];
		this.#state_timestamp = Date.parse(row['state_timestamp'] + '+00:00'); // to parse it UTC
	}

	// ===

	getId() { 
		return this.#id;
	}

	getInterviewee() { 
		return this.#interviewee;
	}

	getInterviewer() { 
		return this.#interviewer;
	}

	getState() { 
		return this.#state;
	}

	getStateTimestamp() { 
		return this.#state_timestamp;
	}

}

// ---

class ManagementOfObjects {

	#of_class;
	#entries = {};

	constructor(of_class_with_methods_update_and_getid) {
		this.#of_class = of_class_with_methods_update_and_getid;
	}

	update(rows, on_add = undefined, on_update = undefined, on_remove = undefined) {
		const entry_keys_to_remove = Object.keys(this.#entries);

		rows.forEach((row) => {
			let entry = this.#entries[row['id']];
	
			if(entry === undefined) {
				entry = this.#entries[row['id']] = new this.#of_class(row);

				if(typeof(on_add) === 'function') {
					on_add(entry);
				}
			}
			else {
				entry.update(row);
				/* remove from */ entry_keys_to_remove.splice(entry_keys_to_remove.indexOf(entry.getId().toString()), 1);

				if(typeof(on_update) === 'function') {
					on_update(entry);
				}
			}
		});

		entry_keys_to_remove.forEach((key) => {
			if(typeof(on_remove) === 'function') {
				on_remove(this.#entries[key]);
			}
			delete this.#entries[key];
		});
	}

	get(id) {
		return this.#entries[id];
	}

	getAll() {
		return Object.values(this.#entries);
	}

}

// ---

class ElementInterviewer extends Observer {

	#container;
		#info_container;
			#info_img;
			#info_p;
		#status_indicator;
		#status_information;

	#interviewer;

	#live_time_counter_interval_id;

	constructor() {
		super();

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

	clearIntervals() {
		clearInterval(this.#live_time_counter_interval_id);
	}

	// ===

	observe(data) {
		if(data instanceof Interviewer === true) {
			let iwer = this.#interviewer = data;
	
			this.#info_img.src = iwer.getImageUrl();
			this.#info_p.innerHTML =
				iwer.getName() + " " +
				"<br>Table: " + iwer.getTable();
		}
		else if(data['notifier'] instanceof Interviewer && data['reason'] === 'interviews') {
			this.clearIntervals();

			let iw = data['notifier'].getInterviewCurrent();

			if(iw === undefined) {
				if(this.#interviewer.getActive() === true) {
					// TODO avaialble maybe better? and display "unavailable (paused)""

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
						iw.getInterviewee().getId()
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
									iw.getInterviewee().getId() +
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
									iw.getInterviewee().getId() +
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

class EmbededElementInterviewer extends ElementInterviewer {

	constructor() {
		super();

		// used to lazily differentiate it on observers
	}

}

class ElementDialogInterviewer extends Observer {
	
	#interviewer_showing = undefined;

	#dialog;
		#embeded_element_interviewer;
		#element_interviews_count;
		#element_interviews;
		#element_interviews_completed_count;
		#element_interviews_completed;

	constructor() {
		super();

		this.#dialog = document.body.appendChild(document.createElement('dialog'));
		this.#dialog.classList.add('dialog_details')

		this.#embeded_element_interviewer = new EmbededElementInterviewer();
		this.#dialog.appendChild(this.#embeded_element_interviewer.get());

		// ===

		let e = this.#dialog.appendChild(document.createElement('div'));
		e.classList.add('quueueue');

		let t = e.appendChild(document.createElement('div'));
		t.classList.add('title_with_count');
		t.appendChild(document.createElement('h3')).innerHTML = 'Enqueued';
		this.#element_interviews_count = t.appendChild(document.createElement('h3'));
		this.#element_interviews_count.classList.add('count');

		// ---

		this.#element_interviews = e.appendChild(document.createElement('div'));
		this.#element_interviews.classList.add('horizontal_scrollable');
		
		// ===

		e = this.#dialog.appendChild(document.createElement('div'));
		e.classList.add('quueueue');

		t = e.appendChild(document.createElement('div'));
		t.classList.add('title_with_count');
		t.appendChild(document.createElement('h3')).innerHTML = 'Completed';
		this.#element_interviews_completed_count = t.appendChild(document.createElement('h3'));
		this.#element_interviews_completed_count.classList.add('count');

		// ---
		
		this.#element_interviews_completed = e.appendChild(document.createElement('div'));
		this.#element_interviews_completed.classList.add('horizontal_scrollable');

		// ===

		let close_button = this.#dialog.appendChild(document.createElement('button'));
		close_button.innerHTML = "Close";
		close_button.addEventListener('click', (event) => {
			this.#dialog.close();
		});

		this.#dialog.addEventListener('close', (event) => {
			this.#interviewer_showing?.observerRemove(this);
			this.#interviewer_showing?.observerRemove(this.#embeded_element_interviewer);
			this.#interviewer_showing = undefined;
			
			this.#embeded_element_interviewer.clearIntervals();
		});
	}

	show_as(interviewer) {
		this.#dialog.close(); // probably not needed
		
		if(interviewer instanceof Interviewer === false) {
			throw new TypeError("Parameter 'interviewer' must be instance of 'Interviewer'");
		}

		this.#interviewer_showing = interviewer;
		this.#interviewer_showing.observerAdd(this.#embeded_element_interviewer);
		this.#interviewer_showing.observerAdd(this);

		this.#dialog.showModal();
	}

	// ===

	observe(data) {
		if(data['notifier'] === this.#interviewer_showing && data['reason'] === 'interviews') {

			const iwer = this.#interviewer_showing;
			const iw_c = iwer.getInterviewCurrent();

			function make_element(interview, completed = false) {
				let iwee = interview.getInterviewee();

				let element = document.createElement('p');
				element.classList.add('interviewee');
				element.textContent = iwee.getId();

				if(completed === true) {
					element.classList.add('interviewee--completed');
					return element;
				}

				if(interview === iw_c) {
					let add_class;

					switch (interview.getState()) {
						case 'CALLING':
							add_class = 'interviewee--calling';
							break;
						case 'DECISION':
							add_class = 'interviewee--decision';
							break;
						case 'HAPPENING':
							add_class = 'interviewee--happening';
							break;
					
						default:
							break;
					}

					element.classList.add(add_class);

					return element;
				}

				element.classList.add(iwee.getAvailable() ? 'interviewee--available' : 'interviewee--unavailable');

				return element;
			}
			
			this.#element_interviews.replaceChildren(...iwer.getInterviews().map(iw => make_element(iw)));
			this.#element_interviews_count.innerHTML = '( ' + iwer.getInterviews().length + ' )';

			// ---
			
			this.#element_interviews_completed.replaceChildren(...iwer.getInterviewsCompleted().map(iw => make_element(iw, true)));
			this.#element_interviews_completed_count.innerHTML = '( ' + iwer.getInterviewsCompleted().length + ' )';
		}
		else if(data instanceof Interviewer) {
			// nothing has to change
		}
		else {
			console.log('Why??');
		}
	}

	// ===

	get() {
		return this.#dialog;
	}

}

// ===

const interviewers	= new ManagementOfObjects(Interviewer);
const interviewees	= new ManagementOfObjects(Interviewee);
const interviews	= new ManagementOfObjects(Interview);

const dialog_details = new ElementDialogInterviewer();

function update(data) {
	calling_time_in_seconds = data['calling_time'];
	
	update_interviewers(data['interviewers']);
	update_interviewees(data['interviewees']);
	update_interviews(data['interviews'], data['interviews_current']);

	display(
		container_interviewers.childElementCount === 1,
		[no_interviewers_message]
	);
}

function update_interviewers(rows) {
	let on_add = (interviewer) => {
		ei = new ElementInterviewer();

		interviewer.observerAdd(ei);

		ei.get().addEventListener('click', (event) => {
			dialog_details.show_as(interviewer);
		});
	
		container_interviewers.appendChild(ei.get());
	};

	let on_remove = (interviewer) => {
		interviewer.observerRemoveAll().forEach((obs) => {
			if(obs instanceof EmbededElementInterviewer === true) {
				// skipping ElementDialogInterviewer will hanlde it if needed
			}
			else if(obs instanceof ElementInterviewer === true) {
				obs.get().parentElement.removeChild(obs.get());
			}
			else if(obs instanceof ElementDialogInterviewer === true) {
				obs.get().close();
			}
			else {
				console.log('Huh??');
			}
		});
	};

	interviewers.update(rows, on_add, undefined, on_remove);
}

function update_interviewees(rows) {
	interviewees.update(rows);
}

function update_interviews(rows, rows_current) {
	rows.forEach((row) => {
		row['interviewer'] = interviewers.get(row['id_interviewer']);
		row['interviewee'] = interviewees.get(row['id_interviewee']);
	});

	// ===
	
	let interviews_array_of_each_interviewer = {};

	interviewers.getAll().forEach((iwer) => {
		interviews_array_of_each_interviewer[iwer.getId()] = [];
	});

	// ---

	let on_add_or_update = (interview) => {
		let iwer = interview.getInterviewer();
		interviews_array_of_each_interviewer[iwer.getId()].push(interview);
	};
	
	interviews.update(rows, on_add_or_update, on_add_or_update, undefined);

	// ---
	
	let interviews_current = {};
	
	rows_current.forEach((row) => {
		let iw = interviews.get(row['id']);
		let iwer = iw.getInterviewer();
		
		interviews_current[iwer.getId()] = iw;
	});

	// ---
	
	interviewers.getAll().forEach((iwer) => {
		iwer.updateInterviews(interviews_array_of_each_interviewer[iwer.getId()], interviews_current[iwer.getId()]);
	});
}
