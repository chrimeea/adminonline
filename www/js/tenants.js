window.AutoSaveModel = Backbone.Model.extend({
	initialize: function() {
		Backbone.Model.prototype.initialize.apply(this, arguments);
		this.on('change', function() {
			this.dirty = true;
		});
		this.on('sync', function() {
			this.dirty = false;
		});
		var errorHandler = function(model, error) {
			$('#content').prepend('<div class="alert alert-warning alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' + error + '</div>');
			var p = model.previousAttributes();
			for (a in model.changedAttributes()) {
				model.set(a, p[a]);
			}
		}
		this.on('error', function(model, xhr, opts) {
			errorHandler(model, xhr.responseText);
		});
		this.on('backgrid:edited', function(model, options) {
			if (!model.dirty || (options && options.save === false)) {
				return;
			}
			if (!model.isValid()) {
				var e = '';
				_.each(model.validationError, function(v, k) {
					e += v;
				});
				errorHandler(model, e);
			} else {
				model.save();
			}
		});
		this.on('backgrid:selected', function(model, options) {
			if (options === true) {
				$('#delete').prop('disabled', false);
				$('#resetPassword').prop('disabled', false);
			} else if (APP.lastView.grid.getSelectedModels().length == 1) {
				$('#delete').prop('disabled', true);
				$('#resetPassword').prop('disabled', true);
			}
		});
	}
});
window.AppView = Backbone.View.extend({
	deleteSelectedModels: function() {
		if (this.grid) {
			_.each(this.grid.getSelectedModels(), function (model) {
				model.destroy({wait: true});
			});
		}
	},
	showErrors: function(model, fieldIds) {
		$('.has-error').find('p').text('');
		$('.has-error').removeClass('has-error');
		_.each(model.validationError, function(v, k) {
			var i = $('#' + fieldIds[k]);
			i.addClass('has-error');
			i.find('p').text(v);
		});
	},
	remove: function() {
		Backbone.View.prototype.remove.apply(this, arguments);
		if (this.grid) {
			this.grid.remove();
		}
		if (this.filter) {
			this.filter.remove();
		}
		$('#delete').prop('disabled', true);
	}
});
window.Upkeep = Backbone.Model.extend();
window.UpkeepCollection = Backbone.Collection.extend({
	model: Upkeep,
	url: "/api/upkeeps"
});
window.Configuration = AutoSaveModel.extend({
	url: "/api/configuration",
	validate: function(attributes) {
		var e = {};
		if (!$.trim(attributes.rate)) {
			e.rate = 'Dobanda este obligatorie';
		} else if (!$.isNumeric(attributes.rate)) {
			e.rate = 'Dobanda nu este corecta';
		}
		if (!$.isEmptyObject(e)) {
			return e;
		}
	}
});
window.User = AutoSaveModel.extend({
	validate: function(attributes) {
		e = {}
		if (!$.trim(attributes.username)) {
			e.username = "Numele este obligatoriu";
		}
		if (!$.trim(attributes.password)) {
			e.password = "Parola este obligatorie";
		}
		if (!$.isEmptyObject(e)) {
			return e;
		}
	}
});
window.UserCollection = Backbone.Collection.extend({
	model: User,
	url: "/api/users"
});
window.Person = AutoSaveModel.extend({
	validate: function(attributes) {
		var e = {}
		if (!$.trim(attributes.name)) {
			e.name = "Numele este obligatoriu";
		}
		if (attributes.email && !/^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/.test(attributes.email)) {
			e.email = "Emailul este incorect";
		}
		if (attributes.telephone && !/(\d| )+$/.test(attributes.telephone)) {
			e.telephone = "Telefonul este incorect";
		}
		if (!$.isEmptyObject(e)) {
			return e;
		}
	}
});
window.PersonCollection = Backbone.Collection.extend({
	model: Person,
	url: "/api/persons"
});
window.PersonRole = Backbone.Model.extend();
window.PersonRoleCollection = Backbone.Collection.extend({
	model: PersonRole,
	url: "/api/person_roles"
});
window.PersonJob = Backbone.Model.extend();
window.PersonJobCollection = Backbone.Collection.extend({
	model: PersonJob,
	url: "/api/person_jobs"
});
window.ModType = Backbone.Model.extend();
window.ModTypeCollection = Backbone.Collection.extend({
	model: ModType,
	url: "/api/mod_types"
});
window.Stair = Backbone.Model.extend({
	idAttribute: "id_stair"
});
window.StairCollection = Backbone.Collection.extend({
	model: Stair,
	url: "/api/stairs"
});
window.Apartment = AutoSaveModel.extend({
	validate: function(attributes) {
		var e = {}
		if (!$.trim(attributes.number)) {
			e.number = 'Numarul este obligatoriu';
		} else if (!/\d+$/.test(attributes.number)) {
			e.number = "Numarul este incorect";
		}
		if (!$.isEmptyObject(e)) {
			return e;
		}
	}
});
window.ApartmentCollection = Backbone.Collection.extend({
	model: Apartment,
	url: "/api/apartments"
});
window.Expense = AutoSaveModel.extend({
	validate: function(attributes) {
		if (!$.trim(attributes.name)) {
			return {name: "Numele este obligatoriu"};
		} else if (attributes.quantity && attributes.quantity <= 0) {
			return {quantity: "Cantitatea trebuie sa fie pozitiva"};
		}
	}
});
window.ExpenseCollection = Backbone.Collection.extend({
	model: Expense,
	url: "/api/expenses"
});
window.Coefficient = AutoSaveModel.extend({
	validate: function(attributes) {
		if (!$.trim(attributes.name)) {
			return {name: "Numele este obligatoriu"};
		}
	}
});
window.CoefficientCollection = Backbone.Collection.extend({
	model: Coefficient,
	url: "/api/coefficients"
});
window.CoefficientValue = AutoSaveModel.extend({
	validate: function(attributes) {
		var e = {};
		_.each(attributes, function(v, a) {	
			if (a.substr(a.length - 4) == '_VAL' && !$.trim(v)) {
				e[a] = 'Campul ' + a.substr(0, a.length - 4) + ' este obligatoriu';
			}
		});
		if (!$.isEmptyObject(e)) {
			return e;
		}
	}
});
window.CoefficientValueCollection = Backbone.Collection.extend({
	model: CoefficientValue,
	url: "/api/coefficient_values"
});
window.CoefficientModValue = AutoSaveModel.extend({
	validate: function(attributes) {
	}
});
window.CoefficientModValueCollection = Backbone.Collection.extend({
	model: CoefficientModValue,
	url: "/api/coefficient_mod_values"
});
window.Index = Backbone.Model.extend({
	validate: function(attributes) {
		var e = {};
		if (isNaN(attributes.index1) || attributes.index1 < attributes.index1_old) {
			e.index1 = 'Indexul curent este mai mic decat cel precedent';
		}
		if (isNaN(attributes.index2) || attributes.index2 < attributes.index2_old) {
			e.index2 = 'Indexul curent este mai mic decat cel precedent';
		}
		if (isNaN(attributes.index3) || attributes.index3 < attributes.index3_old) {
			e.index3 = 'Indexul curent este mai mic decat cel precedent';
		}
		if (!$.isEmptyObject(e)) {
			return e;
		}
	}
});
window.IndexCollection = Backbone.Collection.extend({
	model: Index,
	url: "/api/indexes"
});
window.IndexTable = Backbone.Model.extend();
window.IndexTableCollection = Backbone.Collection.extend({
	model: IndexTable
});
window.InvoiceSeries = AutoSaveModel.extend({
	validate: function(attributes) {
		if (!$.trim(attributes.name)) {
			return {name: "Numele este obligatoriu"};
		}
	}
});
window.InvoiceSeriesCollection = Backbone.Collection.extend({
	model: InvoiceSeries,
	url: "/api/series"
});
window.Payment = AutoSaveModel.extend({
	validate: function(attributes) {
		var e = {};
		if (!$.trim(attributes.value)) {
			e.value = "Valoarea este obligatorie";
		} else if (!$.isNumeric(attributes.value)) {
			e.value = "Valoarea trebuie sa fie numerica";
		}
		if (!$.trim(attributes.number)) {
			e.number = "Numarul este obligatoriu";
		} else if (!$.isNumeric(attributes.number)) {
			e.number = "Numarul trebuie sa fie numeric";
		} else if (attributes.number < 1) {
			e.number = "Numarul trebuie sa fie pozitiv";
		}
		if (!attributes.date) {
			e.date = "Data este obligatorie";
		}
		if (!$.isEmptyObject(e)) {
			return e;
		}
	}
});
window.PaymentCollection = Backbone.Collection.extend({
	model: Payment,
	url: "/api/payments",
	sumByApartment: function(id_apt) {
		return this.reduce(function(s, m) {
			return m.get('id_apartment') == id_apt ? s + m.get('value') : s;
		}, 0);
	},
	maxNumberForSeries: function(id_series, start) {
		if (!start) {
			start = 0;
		}
		return this.reduce(function(s, m) {
			return m.get('id_invoice_series') == id_series ? Math.max(s, m.get('number')) : s;
		}, start);
	}
});
window.Message = AutoSaveModel.extend({
	validate: function(attributes) {
		var e = {};
		if (!$.trim(attributes.message)) {
			e.message = "Mesajul este obligatoriu";
		}
		var d = new Date();
		d.setHours(0);
		d.setMinutes(0);
		d.setSeconds(0);
		d.setMilliseconds(0);
		if (attributes.expire_date < d.getTime() / 1000) {
			e.expire_date = "Data trebuie sa fie in viitor";
		}
		if (!$.isEmptyObject(e)) {
			return e;
		}
	}
});
window.MessageCollection = Backbone.Collection.extend({
	model: Message,
	url: "/api/messages"
});
window.ContactMessage = Backbone.Model.extend({
	url: "api/contact",
	validate: function(attributes) {
		var e = {};
		if (!$.trim(attributes.message)) {
			e.message = "Mesajul este obligatoriu";
		}
		if (!$.trim(attributes.subject)) {
			e.subject = "Subiectul este obligatoriu";
		}
		if (!$.isEmptyObject(e)) {
			return e;
		}
	}
});
window.AppState = Backbone.Model.extend({
	url: "/api/state",
	idAttribute: "id_stair",
	isUserRoot: function() {
		return this.user.get('admin') == 1;
	},
	isUserAdmin: function() {
		return $.inArray(this.get('id_stair'), this.user.get('admin_stairs')) != -1;
	},
	isUpkeepOpen: function() {
		var upkeep = this.get('upkeep');
		if (upkeep) {
			return !upkeep.activation_date && !upkeep.deactivation_date;
		} else {
			return true;
		}
	},
	isUpkeepActive: function() {
		var upkeep = this.get('upkeep');
		if (upkeep) {
			return upkeep.activation_date && !upkeep.deactivation_date;
		} else {
			return false;
		}
	},
	formatWithPrecision: function(n) {
		if (!n) {
			n = 0;
		}
		return n.toFixed(APPSTATE.precision);
	},
	error: function() {
		return Math.pow(10, -APPSTATE.precision);
	}
});
window.TableRow = Backbone.Model.extend({
	idAttribute: "APARTAMENT"
});
window.Table = Backbone.Collection.extend({
	model: TableRow,
	url: "/api/table"
});
window.ChartExpensesDataset = Backbone.Model.extend({
	url: "/api/chart/expenses"
});
window.ChangePassword = AutoSaveModel.extend({
	url: "/api/password/change",
	validate: function(attributes) {
		var e = {}
		if (!$.trim(attributes.new_password)) {
			e.new_password = 'Parola este obligatorie';
		} else if (attributes.new_password != attributes.new_password2) {
			e.new_password = 'Parolele nu se potrivesc';
		}
		if (!$.trim(attributes.old_password)) {
			e.old_password = 'Parola este obligatorie';
		}
		if (!$.isEmptyObject(e)) {
			return e;
		}
	}
});
window.MainUpkeepsView = AppView.extend({
	template: _.template($('#tpl-main-upkeep').html()),
	tagName: 'span',
	events: {
		"change": "changeUpkeep"
	},
	changeUpkeep: function(event) {
		var d = new Date(this.$el.find('#upkeepDatePicker').data("DateTimePicker").getDate().toDate().getTime());
		d.setDate(1);
		APPSTATE.save({'date_upkeep': d.getTime() / 1000},
			{wait: true});
	},
	render: function(eventName) {
		this.$el.html(this.template({
				state: APPSTATE.toJSON()
			}
		));
		var n = new Date();
		n.setHours(0);
		n.setMinutes(0);
		n.setSeconds(0);
		n.setMilliseconds(0);
		var d = APPSTATE.get('date_upkeep');
		var o = APPSTATE.get('oldest_date_upkeep');
		var a = APPSTATE.get('date_upkeep_active');
		this.$el.find('#upkeepDatePicker').datetimepicker({
		    minViewMode: 'months',
		    viewMode: 'months',
                    pickTime: false,
		    startDate: new Date(o * 1000),
		    endDate: n,
		    language: APPSTATE.language,
		    defaultDate: new Date(d * 1000),
		    markedDate: a ? new Date(a * 1000) : new Date(d * 1000)
		});
		if (!APPSTATE.get('upkeep')) {
			this.changeUpkeep();
		}
		return this;
	}
});
window.ButtonsView = AppView.extend({
	template: _.template($('#tpl-buttons').html()),
	render: function(eventName) {
		this.$el.html(this.template());
		return this;
	}
});
window.MainApartmentView = AppView.extend({
	template: _.template($('#tpl-main-apartment').html()),
	tagName: 'span',
	events: {
		"change #mainApartment": "changeApartment"
	},
	initialize: function() {
		this.listenTo(this.model, 'sync', this.render, this);
	},
	changeApartment: function(event) {
		APPSTATE.save({'id_apartment': $(event.currentTarget).val()}, {wait: true});
	},
	render: function(eventName) {
		this.$el.html(this.template({
				apartments: this.model.toJSON(),
				state: APPSTATE.toJSON()
			}
		));
		return this;
	}
});
window.LastMessageView = AppView.extend({
	template: _.template($('#tpl-last-message').html()),
	tagName: 'span',
	initialize: function() {
		this.listenTo(this.model, 'sync', this.render, this);
	},
	render: function(eventName) {
		var d = new Date();
		d.setHours(0);
		d.setMinutes(0);
		d.setSeconds(0);
		d.setMilliseconds(0);
		var k = d.getTime() / 1000;
		var r = this.model.filter(function(t) {
			return t.get('expire_date') >= k;
		});
		var m = '';
		if (r.length > 0) {
			m = _.max(r, function(t) {
				return t.get('expire_date');
			}).get('message');
		}
		this.$el.html(this.template({ last_message: m }
		));
		return this;
	}
});
window.MainSummaryView = AppView.extend({
	template: _.template($('#tpl-main-summary').html()),
	tagName: 'span',
	initialize: function() {
		this.listenTo(this.model.payments, 'remove sync', this.render, this);
		this.listenTo(this.model.apartments, 'remove sync', this.render, this);
		this.listenTo(this.model.table, 'sync', this.render, this);
	},
	render: function(eventName) {
		var s = 0;
		var me = this;
		if (APPSTATE.isUserAdmin()) {
			s = this.model.table.reduce(function(v, t) {
				var o = t.get('Total');
				if (o < 0) {
					return v;
				} else {
					return v + o;
				}
			}, 0);
		} else {
			var a = this.model.apartments.get($("#mainApartment").val());
			var n = a.get('number');
			var t = this.model.table.get(n);
			s = (t ? t.get('Total') : 0);
		}
		this.$el.html(this.template({ summary: s }));
		return this;
	}
});
window.MainView = AppView.extend({
	template: _.template($('#tpl-main').html()),
	events: {
		"change #stairs": "changeStair",
		"click #confirmDelete": "deleteSelection",
		"click #add": "addAction",
		"click #activateUpkeep": "activateUpkeep",
		"click #export": "export",
		"click #changePassword": "changePassword",
		"click #confirmReset": "resetPassword",
		"click #importCoefficients": "importCoefficients",
		"click #importApartments": "importApartments",
		"click #importModCoefficients": "importModCoefficients",
		"click #contact": "editContact"
	},
	initialize: function() {
		this.upkeepView = new MainUpkeepsView();
		this.apartmentView = new MainApartmentView({model: this.model.userApartments});
	},
	deleteSelection: function() {
		if (APP.lastView && APP.lastView.deleteSelectedModels) {
			APP.lastView.deleteSelectedModels();
		}
	},
	addAction: function() {
		if (APP.lastView && APP.lastView.addAction) {
			APP.lastView.addAction();
		}
	},
	resetPassword: function() {
		if (APP.lastView && APP.lastView.grid) {
			_.each(APP.lastView.grid.getSelectedModels(), function (model) {
				$.post("/api/password/reset/" + model.get('id'));
			});
		}
	},
	export: function() {
		window.location.href = '/api/table/export';
	},
	activateUpkeep: function() {
		$.post("/api/upkeeps/activate");
		APPSTATE.get('upkeep').activation_date = new Date();
		APPSTATE.set('date_upkeep_active', APPSTATE.get('date_upkeep'));
		$('#activateUpkeep').prop('disabled', true);
		this.upkeepView.render();
	},
	changePassword: function() {
		var view = new ChangePasswordView();
		$('#changePasswordModal').html(view.render().el);
		$('#changePasswordModal').modal();
	},
	changeStair: function(event) {
		APPSTATE.save({'id_stair': $(event.currentTarget).val()}, {wait: true});
		this.listenTo(APPSTATE, 'sync', function() {
			this.upkeepView.render();
			this.showAssociation();
		}, this);
	},
	importCoefficients: function() {
		var view = new ImportCoefficientsView({
			model: APP.coefficient_values
		});
		$('#importCoefficientsModal').html(view.render().el);
		$('#importCoefficientsModal').modal();
	},
	importApartments: function() {
		var view = new ImportApartmentsView({
			model: APP.apartments
		});
		$('#importApartmentsModal').html(view.render().el);
		$('#importApartmentsModal').modal();
	},
	importModCoefficients: function() {
		var view = new ImportModCoefficientsView({
			model: {coefficient_mod_values: APP.coefficient_mod_values, indexes: APP.indexes}
		});
		$('#importModCoefficientsModal').html(view.render().el);
		$('#importModCoefficientsModal').modal();
	},
	editContact: function() {
		var view = new ContactView();
		$('#contactModal').html(view.render().el);
		$('#contactModal').modal();
	},
	render: function(eventName) {
		this.$el.html(this.template(
			{
				user: this.model.user.toJSON(),
				stairs: this.model.stairs.toJSON(),
				state: APPSTATE.toJSON()
			}
		));
		this.$el.find("#upkeeps_content").html(this.upkeepView.render().el);
		this.$el.find("#apartments_content").html(this.apartmentView.render().el);
		this.showAssociation();
		return this;
	},
	showAssociation: function() {
		var a = APPSTATE.get('association');
		this.$el.find('#association').html(APPSTATE.user.get('full_name') + ', ' + a.name);
	},
	remove: function() {
		AppView.prototype.remove.apply(this, arguments);
		this.upkeepView.remove();
		this.apartmentView.remove();
	}
});
window.DefineApartmentsView = AppView.extend({
	template: _.template($('#tpl-define-apartments').html()),
	initialize: function() {
		var me = this;
		me.p = [];
		this.grid = new Backgrid.Grid({
			columns: [],
			collection: this.model.apartments,
			footer: Backgrid.Footer.extend({
				template: _.template($('#tpl-define-apartments-footer').html()),
				initialize: function() {
					Backgrid.Footer.prototype.initialize.apply(this, arguments);
					this.listenTo(me.model.payments, 'remove sync change:[value]', this.render, this);
				},
				render: function() {
					var footer = [0, 0, 0, 0];
					me.model.apartments.each(function(m) {
						var id = m.get('id');
						var t = me.model.table.get(m.get('number'));
						if (t) {
							var v = t.get('Avans');
							if (v) {
								footer[0] += v;
							}
							v = t.get('Restanta');
							if (v) {
								footer[1] += v;
							}
							v = t.get('Penalizare');
							if (v) {
								footer[2] += v;
							}
							footer[3] += t.get('Total');
						}
					});
					this.$el.html(this.template({total: footer, canSelectRow: APPSTATE.isUserRoot()}));
					return this;
				}
			})
		});
		this.filter = new Backgrid.Extension.ClientSideFilter({
			collection: this.model.apartments,
			fields: ['number', 'name'],
			wait: APPSTATE.filterDelay,
			className: 'backgrid-filter form-search hidden-print'
		});
		this.listenTo(this.model.persons, 'remove sync', this.renderGrid, this);
		this.listenTo(this.model.apartments, 'remove sync', this.renderGrid, this);
		this.listenTo(this.model.payments, 'remove sync', this.renderGrid, this);
		this.listenTo(this.model.table, 'sync', this.renderGrid, this);
	},
	renderGrid: function() {
		var me = this;
		var persons = this.model.persons;
		var table = this.model.table;
		var payments = this.model.payments;
		var columns = [];
		if (APPSTATE.isUserRoot()) {
			columns.push({ name: "", cell: "select-row", headerCell: "select-all"});
		}
		this.grid.columns.reset(columns.concat([
				{ name: "number", label: "Apartament", cell: "integer", editable: false },
				{ name: "name", label: "Tip", editable: APPSTATE.isUserAdmin(),
					cell: Backgrid.SelectCell.extend({
						optionValues: function() {
							return [['Garsoniera', 'Garsoniera'], ['O camera', 'O camera'],
							['2 camere', '2 camere'], ['3 camere', '3 camere'],
							['4 camere', '4 camere'], ['Duplex', 'Duplex'], ['Penthaus', 'Penthaus']];
						}
					})},
				{ label: "Avans", cell: "number", editable: false,
					sortValue: function(m, s) {
						return table.get(m.get('number')).get('Avans');
					},
					formatter: {
						fromRaw: function (rawData, model) {
							var t = table.get(model.get('number'));
							return APPSTATE.formatWithPrecision(t ? t.get('Avans') : 0);
						}}},
				{ label: "Restanta", cell: "number", editable: false,
					sortValue: function(m, s) {
						return table.get(m.get('number')).get('Restanta');
					},
					formatter: {
						fromRaw: function (rawData, model) {
							var t = table.get(model.get('number'));
							return APPSTATE.formatWithPrecision(t ? t.get('Restanta') : 0);
						}}},
				{ label: "Penalizare", cell: "number", editable: false,
					sortValue: function(m, s) {
						return table.get(m.get('number')).get('Penalizare');
					},
					formatter: {
						fromRaw: function (rawData, model) {
							var t = table.get(model.get('number'));
							return APPSTATE.formatWithPrecision(t ? t.get('Penalizare') : 0);
						}}},
				{ label: "De platit", cell: "number", editable: false,
					sortValue: function(m, s) {
						return table.get(m.get('number')).get('Total');
					},
					formatter: {
						fromRaw: function (rawData, model) {
							var t = table.get(model.get('number'));
							return APPSTATE.formatWithPrecision(t ? t.get('Total') : 0);
						}}},
				{ name: "id_person", label: "Contact", editable: APPSTATE.isUserRoot(),
					sortValue: function(m, s) {
						return persons.get(m.get(s)).get('name');
					},
					cell: Backgrid.SelectCell.extend({
						optionValues: function() {
							var personOptions = persons.map(function(m) {
								return [m.get('name'), m.get('id')];
							});
							personOptions.splice(0, 0, [null, null]);
							return personOptions;
						}
					})
				}
			]));
	},
	render: function(eventName) {
		this.$el.html(this.template());
		var elem = this.$el.find("#apartments");
		elem.append(new ButtonsView().render().el);
		elem.find('#importApartments').removeClass('hidden');
		if (!APPSTATE.isUserRoot()) {
			elem.find('#add').addClass('hidden');
			elem.find('#delete').addClass('hidden');
		}
		elem.find('#viewfilter').append(this.filter.render().el);
		this.renderGrid();
		elem.append(this.grid.render().el);
		return this;
	},
	addAction: function() {
		var view = new AddApartmentView({
			model: {
				persons: this.model.persons
			}
		});
		$('#addApartmentModal').html(view.render().el);
		$('#addApartmentModal').modal();
	}
});
window.DefineUsersView = AppView.extend({
	template: _.template($('#tpl-define-users').html()),
	initialize: function() {
		this.grid = new Backgrid.Grid({
			columns: [],
			collection: this.model.users
		});
		this.filter = new Backgrid.Extension.ClientSideFilter({
			collection: this.model.users,
			fields: ['username', 'contact'],
			wait: APPSTATE.filterDelay,
			className: 'backgrid-filter form-search hidden-print'
		});
		this.listenTo(this.model.persons, 'remove sync', this.renderGrid, this);
		this.listenTo(this.model.apartments, 'remove sync', this.renderGrid, this);
	},
	renderGrid: function() {
		var persons = this.model.persons;
		var apartments = this.model.apartments;
		var columns = [];
		if (APPSTATE.isUserAdmin()) {
			columns.push({ name: "", cell: "select-row", headerCell: "select-all"});
		}
		this.grid.columns.reset(columns.concat([
				{ name: "username", label: "Utilizator", cell: "string", editable: APPSTATE.isUserAdmin() },
				{ name: "id_person", label: "Contact", editable: APPSTATE.isUserAdmin(),
					sortValue: function(m, s) {
						return persons.get(m.get(s)).get('name');
					},
					cell: Backgrid.SelectCell.extend({
						optionValues: function() {
							var personOptions = persons.length == 0 ? [['', 0]] : persons.map(function(m) {
								return [m.get('name'), m.get('id')];
							});
							return personOptions;
						}
					})
				},
				{ name: "id_apartment", label: "Acces", editable: APPSTATE.isUserAdmin(),
					sortValue: function(m, s) {
						return apartments.get(m.get(s)).get('number');
					},
					cell: Backgrid.SelectCell.extend({
						optionValues: function() {
							var apartmentOptions = apartments.length == 0 ? [['', 0]] : apartments.map(function(m) {
								return [m.get('number'), m.get('id')];
							});
							apartmentOptions.splice(0, 0, ['Administrator', 0]);
							return apartmentOptions;
						}
					})
				}
			]));
	},
	render: function(eventName) {
		this.renderGrid();
		this.$el.html(this.template());
		var elem = this.$el.find("#users");
		elem.append(new ButtonsView().render().el);
		elem.find('#viewfilter').append(this.filter.render().el);
		elem.append(this.grid.render().el);
		elem.find('#resetPassword').removeClass('hidden');
		return this;
	},
	addAction: function() {
		var view = new AddUserView({
			model: {
				persons: this.model.persons,
				apartments: this.model.apartments
			}
		});
		$('#addUserModal').html(view.render().el);
		$('#addUserModal').modal();
	}
});
window.DefinePersonsView = AppView.extend({
	template: _.template($('#tpl-define-persons').html()),
	initialize: function() {
		this.grid = new Backgrid.Grid({
			columns: [],
			collection: this.model.persons
		});
		this.filter = new Backgrid.Extension.ClientSideFilter({
			collection: this.model.persons,
			fields: ['name', 'email'],
			wait: APPSTATE.filterDelay,
			className: 'backgrid-filter form-search hidden-print'
		});
		this.listenTo(this.model.apartments, 'remove sync', this.renderGrid, this);
	},
	renderGrid: function() {
		var personRoles = this.model.personRoles;
		var personJobs = this.model.personJobs;
		var apartments = this.model.apartments;
		var columns = [];
		if (APPSTATE.isUserAdmin()) {
			columns.push({ name: "", cell: "select-row", headerCell: "select-all"});
		}
		this.grid.columns.reset(columns.concat([
				{ name: "name", label: "Nume", cell: "string", editable: APPSTATE.isUserAdmin() },
				{ name: "telephone", label: "Telefon", cell: "string", editable: APPSTATE.isUserAdmin() },
				{ name: "email", label: "E-Mail", cell: "string", editable: APPSTATE.isUserAdmin() },
				{ name: "notify", label: "Notificare", editable: false,
					cell: Backgrid.SelectCell.extend({
						optionValues: function() {
							return [['Nu', 0], ['Da', 1]];
						}
					})},
				{ name: "id_person_role", label: "Rol", editable: APPSTATE.isUserAdmin(),
					sortValue: function(m, s) {
						return personRoles.get(m.get(s)).get('name');
					},
					cell: Backgrid.SelectCell.extend({
						optionValues: function() {
							var personRoleOptions = personRoles.length == 0 ? [['', 0]] : personRoles.map(function(m) {
								return [m.get('name'), m.get('id')];
							});
							return personRoleOptions;
						}
					})
				},
				{ name: "id_person_job", label: "Pozitie", editable: APPSTATE.isUserAdmin(),
					sortValue: function(m, s) {
						return personJobs.get(m.get(s)).get('name');
					},
					cell: Backgrid.SelectCell.extend({
						optionValues: function() {
							var personJobOptions = personJobs.length == 0 ? [['', 0]] : personJobs.map(function(m) {
								return [m.get('name'), m.get('id')];
							});
							return personJobOptions;
						}
					})
				},
				{ name: "id_apartment", label: "Apartament", editable: APPSTATE.isUserAdmin(),
					sortValue: function(m, s) {
						return apartments.get(m.get(s)).get('number');
					},
					cell: Backgrid.SelectCell.extend({
						optionValues: function() {
							var apartmentOptions = apartments.map(function(m) {
								return [m.get('number'), m.get('id')];
							});
							apartmentOptions.splice(0, 0, [null, null]);
							return apartmentOptions;
						}
					})
				}
			])
		);
	},
	render: function(eventName) {
		this.renderGrid();
		this.$el.html(this.template());
		var elem = this.$el.find("#persons");
		elem.append(new ButtonsView().render().el);
		elem.find('#viewfilter').append(this.filter.render().el);
		elem.append(this.grid.render().el);
		return this;
	},
	addAction: function() {
		var view = new AddPersonView({
			model: {
				apartments: this.model.apartments,
				personRoles: this.model.personRoles,
				personJobs: this.model.personJobs,
				stairs: this.model.stairs
			}
		});
		$('#addPersonModal').html(view.render().el);
		$('#addPersonModal').modal();
	}
});
window.ExpensesView = AppView.extend({
	template: _.template($('#tpl-define-expenses').html()),
	configurationTemplate: _.template($('#tpl-define-configuration').html()),
	events: {
		"change #configRate": "setRate"
	},
	initialize: function() {
		var mdl = this.model.expenses;
		this.grid = new Backgrid.Grid({
			columns: [],
			collection: this.model.expenses,
			footer: Backgrid.Footer.extend({
				template: _.template($('#tpl-define-expenses-footer').html()),
				initialize: function() {
					Backgrid.Footer.prototype.initialize.apply(this, arguments);
					this.listenTo(mdl, 'remove sync change:[value]', this.render, this);
				},
				render: function() {
					var footer = [0, 0];
					mdl.each(function(m) {
						var v = m.get('value');
						if (v) {
							footer[0] += v;
						}
						v = m.get('quantity');
						if (v) {
							footer[1] += v;
						}
					});
					this.$el.html(this.template({total: footer, canSelectRow: APPSTATE.isUserRoot() && APPSTATE.isUpkeepOpen()}));
					return this;
				}
			})
		});
		this.filter = new Backgrid.Extension.ClientSideFilter({
			collection: this.model.expenses,
			fields: ['name', 'supplier'],
			wait: APPSTATE.filterDelay,
			className: 'backgrid-filter form-search hidden-print'
		});
		this.listenTo(this.model.expenses, 'remove sync', this.renderGrid, this);
		this.listenTo(this.model.configuration, 'sync', this.renderConfiguration, this);
	},
	renderGrid: function() {
		if (APPSTATE.isUpkeepOpen()) {
			this.$el.find('#add').prop('disabled', false);
		} else {
			this.$el.find('#add').prop('disabled', true);
		}
		var coefficients = this.model.coefficients;
		var columns = [];
		if (APPSTATE.isUserRoot() && APPSTATE.isUpkeepOpen()) {
			columns.push({ name: "", cell: "select-row", headerCell: "select-all"});
		}
		this.grid.columns.reset(columns.concat([
				{ name: "name", label: "Nume", cell: "string", editable: APPSTATE.isUserAdmin() },
				{ name: "title", label: "Titlu", cell: "string", editable: APPSTATE.isUserAdmin() },
				{ name: "supplier", label: "Furnizor", cell: "string", editable: APPSTATE.isUserAdmin() },
				{ name: "unit", label: "Unitate", editable: APPSTATE.isUserAdmin(),
					cell: Backgrid.SelectCell.extend({
						optionValues: function() {
							return [['', ''], ['Metru patrat', 'm.p.'], ['Metru cub', 'm.c.']];
						}})},
				{ name: "id_coefficient", label: "Coeficient", editable: APPSTATE.isUserRoot(),
					sortValue: function(m, s) {
						return coefficients.get(m.get(s)).get('name');
					},
					cell: Backgrid.SelectCell.extend({
						optionValues: function() {
							var coefficientOptions = coefficients.map(function(m) {
								return [m.get('name'), m.get('id')];
							});
							return coefficientOptions;
						}
					})
				},
				{name: "value", label: "Valoare", cell: Backgrid.NumberCell.extend({decimals: APPSTATE.precision}),
					editable: APPSTATE.isUserAdmin() && APPSTATE.isUpkeepOpen() },
				{name: "quantity", label: "Cantitate", cell: Backgrid.NumberCell.extend({decimals: APPSTATE.precision}),
					editable: APPSTATE.isUserAdmin() && APPSTATE.isUpkeepOpen() }
			]));
	},
	renderConfiguration: function() {
		if (APPSTATE.isUserAdmin()) {
			this.$el.find("#configuration").html(this.configurationTemplate({
				configuration: this.model.configuration.toJSON()
			}));
			var elem = this.$el.find('#configRate');
			if (APPSTATE.isUpkeepOpen()) {
				elem.removeAttr('disabled');
			} else {
				elem.attr('disabled','disabled');
			}
		}
	},
	render: function(eventName) {
		this.$el.html(this.template());
		this.renderConfiguration();
		var elem = this.$el.find("#expenses");
		elem.append(new ButtonsView().render().el);
		elem.find('#viewfilter').append(this.filter.render().el);
		this.renderGrid();
		elem.append(this.grid.render().el);
		return this;
	},
	addAction: function() {
		var view = new AddExpenseView({
			model: {
				coefficients: this.model.coefficients
			}
		});
		$('#addExpenseModal').html(view.render().el);
		$('#addExpenseModal').modal();
	},
	setRate: function(event) {
		this.model.configuration.set('rate', $(event.currentTarget).val());
		this.model.configuration.save();
	}
});
window.EditCoefficientsView = AppView.extend({
	template: _.template($('#tpl-edit-coefficients').html()),
	initialize: function() {
		var me = this;
		this.grid = new Backgrid.Grid({
			columns: [],
			collection: this.model.coefficient_values,
			footer: Backgrid.Footer.extend({
				template: _.template($('#tpl-edit-coefficients-footer').html()),
				initialize: function() {
					Backgrid.Footer.prototype.initialize.apply(this, arguments);
					this.listenTo(me.model.coefficient_values, 'remove sync', this.render, this);
				},
				render: function() {
					var footer = {};
					me.model.coefficient_values.each(function(m) {
						_.each(m.attributes, function(i, j) {
							if (j.substr(j.length - 4) == '_VAL') {
								var root = j.substr(0, j.length - 4);
								if (!(root in footer)) {
									footer[root] = 0;
								}
								footer[root] += m.get(j);
							}
						});
					});
					this.$el.html(this.template({total: footer}));
					return this;
				}
			})
		});
		this.listenTo(this.model.coefficient_values, 'remove sync', this.renderGrid, this);
	},
	renderGrid: function() {
		if (this.model.coefficient_values.length > 0) {
			if (APPSTATE.isUserRoot() && APPSTATE.isUpkeepOpen()) {
				this.$el.find('#importCoefficients').removeClass('hidden');
			}
			var columns = [];
			var me = this;
			_.each(this.model.coefficient_values.at(0).attributes, function(v, k) {
				if (k.indexOf('id') != 0) {
					if (k.substr(k.length - 4) == '_VAL') {
						columns.push({
							name: k,
							label: k.substr(0, k.length - 4),
							cell: Backgrid.NumberCell.extend({decimals: APPSTATE.precision}),
							editable: APPSTATE.isUserAdmin() && APPSTATE.isUpkeepOpen()
						});
					}
				} else {
					columns.push({name: k, label: 'Apartament', editable: false,
					cell: Backgrid.SelectCell.extend({
						optionValues: function() {
							return me.model.apartments.map(function(m) {
								return [m.get('number'), m.get('id')];
							});
						}
					})});
				}
			});
			this.grid.columns.reset(columns);
		}
	},
	render: function(eventName) {
		this.$el.html(this.template());
		var elem = this.$el.find("#edit_coefficients");
		elem.append(new ButtonsView().render().el);
		if (APPSTATE.isUserAdmin()) {
			elem.find('#add').addClass('hidden');
			elem.find('#delete').addClass('hidden');
		}
		this.renderGrid();
		elem.append(this.grid.render().el);
		return this;
	}
});
window.EditCoefficientModsView = AppView.extend({
	template: _.template($('#tpl-edit-coefficient-mods').html()),
	initialize: function() {
		this.grid = new Backgrid.Grid({
			columns: [],
			collection: this.model.coefficient_mod_values
		});
		this.listenTo(this.model.coefficient_mod_values, 'remove sync', this.renderGrid, this);
	},
	render: function(eventName) {
		this.$el.html(this.template());
		var elem = this.$el.find("#edit_coefficient_mods");
		elem.append(new ButtonsView().render().el);
		if (APPSTATE.isUserAdmin()) {
			elem.find('#add').addClass('hidden');
			elem.find('#delete').addClass('hidden');
		}
		this.renderGrid();
		elem.append(this.grid.render().el);
		return this;
	},
	renderGrid: function() {
		if (this.model.coefficient_mod_values.length > 0) {
			if (APPSTATE.isUserRoot() && APPSTATE.isUpkeepOpen()) {
				this.$el.find('#importModCoefficients').removeClass('hidden');
			}
			var indx = this.model.indexes;
			var mods = this.model.modTypes;
			var me = this;
			var columns = [];
			_.each(this.model.coefficient_mod_values.at(0).attributes, function(v, k) {
				if (k.indexOf('id') != 0) {
					if (k.substr(k.length - 3) == '_ID') {
						var root = k.substr(0, k.length - 3);
						columns.push({
							name: k,
							label: root,
							cell: Backgrid.Cell.extend({
								template: _.template($('#tpl-view-coefficient-mods-cell').html()),
								render: function(e) {
									var tpe = '';
									if (mods) {
										tpe = mods.get(this.model.get(root + '_TYPE'));
										if (tpe) {
											tpe = tpe.get('name');
										}
									}
									this.$el.html(this.template({
										theType: tpe,
										theTypeID: this.model.get(root + '_TYPE'),
										theValue: this.model.get(root + '_VAL')
									}));
									this.delegateEvents();
									return this;
								},
								editor: Backgrid.CellEditor.extend({
									template: _.template($('#tpl-edit-coefficient-mods-cell').html()),
									tagName: "div",
									className: "modal fade",
									events: {
										 "submit": "saveOrCancel",
										 "reset": "cancel",
										 "hidden.bs.modal": "close",
										 "change #coefficientModCellType": "updateValue",
										 "change #coefficientModCellIndex1": "updateTotal",
										 "change #coefficientModCellIndex2": "updateTotal",
										 "change #coefficientModCellIndex3": "updateTotal"
									},
									render: function() {
										var t = this.model.get(root + '_TYPE');
										var i = this.getIndex();
										this.$el.html(this.template({
											theType: t,
											theValue: this.model.get(root + '_VAL'),
											theOptions: this.optionValues(),
											column: this.column,
											index: i ? i.toJSON() : {index1: 0, index2: 0, index3: 0, index1_old: 0, index2_old: 0, index3_old: 0}
										}));
										this.$el.modal();
										this.delegateEvents();
										return this;
									},
									optionValues: function() {
										var modsOptions = mods.map(function(m) {
											return [m.get('name'), m.get('id')];
										});
										return modsOptions;
									},
									cancel: function(e) {
										this.$el.modal("hide");
									},
									close: function(e) {
										this.model.trigger("backgrid:edited", this.model, this.column, new Backgrid.Command(e));
									},
									saveOrCancel: function(e) {
										e.preventDefault();
										e.stopPropagation();
										var t = parseInt(this.$el.find(':selected').val());
										this.model.set(root + '_TYPE', t);
										var d = {};
										var v = 0;
										var ok = true;
										if (t == 2) {
											v = parseFloat(this.$el.find('#coefficientModCellValue2').val());
										} else if (t == 3) {
											var index1 = parseFloat(this.$el.find('#coefficientModCellIndex1').val());
											var index2 = parseFloat(this.$el.find('#coefficientModCellIndex2').val());
											var index3 = parseFloat(this.$el.find('#coefficientModCellIndex3').val());
											if (index1 || index2 || index3) {
											v = index1 + index2 + index3;
											var i = this.getIndex();
											if (i) {
												v -= i.get('estimated');
												if (i.get('index1_old') || i.get('index2_old') || i.get('index3_old')) {
													v -= i.get('index1_old') + i.get('index2_old') + i.get('index3_old');
												}
												i.set('index1', index1);
												i.set('index2', index2);
												i.set('index3', index3);
												i.save();
											} else {
												i = indx.create({id_apartment: this.model.get('id'),
													id_expense: this.model.get(root + '_IDEXP'),
													index1: index1,
													index2: index2,
													index3: index3,
													index1_old: 0,
													index2_old: 0,
													index3_old: 0,
													estimated: 0});
											}
											if (i.validationError) {
												me.showErrors(i, {index1: 'coefficientModCellIndex1Group',
														index2: 'coefficientModCellIndex2Group',
														index3: 'coefficientModCellIndex3Group'});
												ok = false;
											}
											}
										} else if (t == 4) {
											v = parseFloat(this.$el.find('#coefficientModCellValue4').val());
										}
										if (ok) {
											d[root + '_VAL'] = v;
											this.model.set(d);
											this.$el.modal("hide");
										}
									},
									updateValue: function(event) {
										var t = parseInt(this.$el.find(':selected').val());
										for (var i = 1; i <= 4; i++) {
											if (i == t) {
												this.$el.find('#coefficientModCellTab' + i).removeClass('hidden');
											} else {
												this.$el.find('#coefficientModCellTab' + i).addClass('hidden');
											}
										}
									},
									updateTotal: function() {
										var i = this.getIndex();
										var v = this.$el.find('#coefficientModCellIndex1').val();
										var t1 = v ? parseFloat(v) - (i && i.get('index1_old') ? i.get('index1_old') : 0) : 0;
										v = this.$el.find('#coefficientModCellIndex2').val();
										var t2 = v ? parseFloat(v) - (i && i.get('index2_old') ? i.get('index2_old') : 0) : 0; 
										v = this.$el.find('#coefficientModCellIndex3').val();
										var t3 = v ? parseFloat(v) - (i && i.get('index3_old') ? i.get('index3_old') : 0) : 0;
										var e = i ? i.get('estimated') : 0;
										this.$el.find('#coefficientModCellTotal1').text(t1.toFixed(APPSTATE.precision));
										this.$el.find('#coefficientModCellTotal2').text(t2.toFixed(APPSTATE.precision));
										this.$el.find('#coefficientModCellTotal3').text(t3.toFixed(APPSTATE.precision));
										this.$el.find('#coefficientModCellTotal').text((t1 + t2 + t3 - e).toFixed(APPSTATE.precision));
									},
									getIndex: function() {
										var idapt = this.model.get('id');
										var idexp = this.model.get(root + '_IDEXP');
										return indx.find(function(m) {
											return m.get('id_apartment') == idapt && m.get('id_expense') == idexp;
										});
									}
								})
							}),
							editable: APPSTATE.isUserAdmin()
						});
					}
				} else {
					columns.push({name: k, label: 'Apartament', editable: false,
						cell: Backgrid.SelectCell.extend({
						optionValues: function() {
							return me.model.apartments.map(function(m) {
								return [m.get('number'), m.get('id')];
							});
						}
					})});
				}
			});
			if (columns.length > 0) {
				this.grid.columns.reset(columns);
			}
		}
	}
});
window.PaymentsView = AppView.extend({
	template: _.template($('#tpl-define-payments').html()),
	initialize: function() {
		var mdl = this.model.payments;
		this.grid = new Backgrid.Grid({
			columns: [],
			collection: this.model.payments,
			footer: Backgrid.Footer.extend({
				template: _.template($('#tpl-define-payments-footer').html()),
				initialize: function() {
					Backgrid.Footer.prototype.initialize.apply(this, arguments);
					this.listenTo(mdl, 'remove sync change:[value]', this.render, this);
				},
				render: function() {
					var footer = 0;
					mdl.each(function(m) {
						footer += m.get('value');
					});
					this.$el.html(this.template({total: footer, canSelectRow: APPSTATE.isUserAdmin() && APPSTATE.isUpkeepActive()}));
					return this;
				}
			})
		});
		this.listenTo(this.model.payments, 'remove sync', this.renderGrid, this);
		this.listenTo(this.model.series, 'remove sync', this.renderGrid, this);
		this.listenTo(this.model.apartments, 'remove sync', this.renderGrid, this);
	},
	renderGrid: function() {
		var series = this.model.series;
		if (APPSTATE.isUpkeepActive() && series.length > 0) {
			this.$el.find('#add').prop('disabled', false);
		} else {
			this.$el.find('#add').prop('disabled', true);
		}
		var apartments = this.model.apartments;
		var columns = [];
		if (APPSTATE.isUserAdmin() && APPSTATE.isUpkeepActive()) {
			columns.push({ name: "", cell: "select-row", headerCell: "select-all"});
		}
		this.grid.columns.reset(columns.concat([
				{ name: "id_apartment", label: "Apartament", editable: APPSTATE.isUserAdmin() && APPSTATE.isUpkeepActive(),
					sortValue: function(m, s) {
						return apartments.get(m.get(s)).get('number');
					},
					cell: Backgrid.SelectCell.extend({
						optionValues: function() {
							var apartmentOptions = apartments.map(function(m) {
								return [m.get('number'), m.get('id')];
							});
							apartmentOptions.splice(0, 0, [null, null]);
							return apartmentOptions;
						}
					})
				},
				{ name: "id_invoice_series", label: "Serie", editable: APPSTATE.isUserAdmin() && APPSTATE.isUpkeepActive(),
					sortValue: function(m, s) {
						return series.get(m.get(s)).get('name');
					},
					cell: Backgrid.SelectCell.extend({
						optionValues: function() {
							var seriesOptions = series.map(function(m) {
								return [m.get('name'), m.get('id')];
							});
							seriesOptions.splice(0, 0, [null, null]);
							return seriesOptions;
						}
					})
				},
				{ name: "number", label: "Numar", cell: "integer", editable: APPSTATE.isUserAdmin() && APPSTATE.isUpkeepActive() },
				{ name: "value", label: "Valoare", cell: Backgrid.NumberCell.extend({decimals: APPSTATE.precision}),
					editable: APPSTATE.isUserAdmin() && APPSTATE.isUpkeepActive() },
				{ name: "date", label: "Data", cell: "date", editable: APPSTATE.isUserAdmin() && APPSTATE.isUpkeepActive(),
					formatter: {
						fromRaw: function (rawData, model) {
							var date = new Date(rawData * 1000);
							return date.getDate() + '/' + (date.getMonth() + 1) + '/' + date.getFullYear();
						},
						toRaw: function (formattedData, model) {
							var dateParts = formattedData.split('/');
							return new Date(dateParts[2], dateParts[1] - 1, dateParts[0]).getTime() / 1000;
						}
					}
				}
			]));
	},
	render: function(eventName) {
		this.$el.html(this.template());
		var elem = this.$el.find("#edit_payments");
		elem.append(new ButtonsView().render().el);
		this.renderGrid();
		elem.append(this.grid.render().el);
		return this;
	},
	addAction: function() {
		var view = new AddPaymentView({
			model: {
				apartments: this.model.apartments,
				series: this.model.series,
				payments: this.model.payments,
				table: this.model.table
			}
		});
		$('#addPaymentModal').html(view.render().el);
		$('#addPaymentModal').modal();
	}
});
window.InvoiceSeriesView = AppView.extend({
	template: _.template($('#tpl-define-series').html()),
	initialize: function() {
		var me = this;
		var columns = [];
		if (APPSTATE.isUserRoot()) {
			columns.push({ name: "", cell: "select-row", headerCell: "select-all"});
		}
		columns = columns.concat([
				{ name: "name", label: "Nume", cell: "string", editable: APPSTATE.isUserAdmin() },
				{ label: "Numar", cell: "integer", editable: false,
					formatter: {
						fromRaw: function (rawData, model) {
							return me.model.payments.maxNumberForSeries(model.get('id'), model.get('number'));
						}}}
			]);
		this.grid = new Backgrid.Grid({
			columns: columns,
			collection: this.model.series
		});
		this.filter = new Backgrid.Extension.ClientSideFilter({
			collection: this.model.series,
			fields: ['name'],
			wait: APPSTATE.filterDelay,
			className: 'backgrid-filter form-search hidden-print'
		});
		this.listenTo(this.model.series, 'remove sync', this.render, this);
		this.listenTo(this.model.payments, 'remove sync', this.render, this);
	},
	render: function(eventName) {
		this.$el.html(this.template());
		var elem = this.$el.find("#edit_series");
		elem.append(new ButtonsView().render().el);
		elem.find('#viewfilter').append(this.filter.render().el);
		elem.append(this.grid.render().el);
		return this;
	},
	addAction: function() {
		var view = new AddSeriesView();
		$('#addSeriesModal').html(view.render().el);
		$('#addSeriesModal').modal();
	}
});
window.MessagesView = AppView.extend({
	template: _.template($('#tpl-define-messages').html()),
	initialize: function() {
		var columns = [];
		if (APPSTATE.isUserAdmin()) {
			columns.push({ name: "", cell: "select-row", headerCell: "select-all"});
		}
		columns = columns.concat([
				{ name: "created_date", label: "Data creare", cell: "date", editable: false,
					formatter: {
						fromRaw: function (rawData, model) {
							var date = new Date(rawData * 1000);
							return date.getDate() + '/' + (date.getMonth() + 1) + '/' + date.getFullYear();
						}
					}
				},
				{ name: "expire_date", label: "Data expirare", cell: "date", editable: false,
					formatter: {
						fromRaw: function (rawData, model) {
							var date = new Date(rawData * 1000);
							return date.getDate() + '/' + (date.getMonth() + 1) + '/' + date.getFullYear();
						}
					}
				},
				{ name: "message", label: "Mesaj", cell: "string", editable: APPSTATE.isUserAdmin() }
			]);
		this.grid = new Backgrid.Grid({
			columns: columns,
			collection: this.model
		});
		this.filter = new Backgrid.Extension.ClientSideFilter({
			collection: this.model,
			fields: ['message'],
			wait: APPSTATE.filterDelay,
			className: 'backgrid-filter form-search hidden-print'
		});
	},
	render: function(eventName) {
		this.$el.html(this.template());
		var elem = this.$el.find("#edit_messages");
		elem.append(new ButtonsView().render().el);
		elem.find('#viewfilter').append(this.filter.render().el);
		elem.append(this.grid.render().el);
		return this;
	},
	addAction: function() {
		var view = new AddMessagesView();
		$('#addMessagesModal').html(view.render().el);
		$('#addMessagesModal').modal();
	}
});
window.TablesView = AppView.extend({
	template: _.template($('#tpl-view-tables').html()),
	initialize: function() {
		this.listenTo(this.model.expenses, 'remove sync', this.render, this);
		this.listenTo(this.model.coefficient_values, 'remove sync', this.render, this);
	},
	render: function(eventName) {
		var tables = [];
		var me = this;
		this.model.expenses.each(function(m) {
			var id = m.get('id_coefficient');
			if (!(id in tables)) {
				tables[id] = {total: 0, expenses: [], apartments: {}};
			}
			tables[id].expenses.push({name: m.get('name'), value: m.get('value'), quantity: m.get('quantity'), unit: m.get('unit')});
			tables[id].unit = me.model.coefficients.get(id).get('unit');
		});
		this.model.coefficient_values.each(function(m) {
			_.each(m.attributes, function(v, k) {
				if (k.length > 4 && k.substr(k.length - 4) == '_VAL') {
					var root = k.substr(0, k.length - 4);
					var id = m.get(root + '_IDCF');
					if (id in tables) {
						tables[id].total += m.get(k);
						tables[id].name = root;
						var apt = me.model.apartments.get(m.get('id')).get('name');
						if ('apartments' in tables[id]) {
							if (!(apt in tables[id].apartments)) {
								tables[id].apartments[apt] = m.get(k);
							} else if (tables[id].apartments[apt] != m.get(k)) {
								delete tables[id].apartments;
							}
						}
					}
				}
			});
		});
		this.$el.html(this.template({tables: tables}));
		return this;
	}
});
window.TableView = AppView.extend({
	template: _.template($('#tpl-view-table').html()),
	initialize: function() {
		var me = this;
		this.footer = {};
		this.grid = new Backgrid.Grid({
			columns: [],
			collection: this.model.table,
			footer: Backgrid.Footer.extend({
				template: _.template($('#tpl-view-total-table').html()),
				render: function() {
					this.$el.html(this.template({total: me.footer}));
					return this;
				}
			})
		});
		this.filter = new Backgrid.Extension.ClientSideFilter({
			collection: this.model.table,
			fields: ['APARTAMENT'],
			wait: APPSTATE.filterDelay,
			className: 'backgrid-filter form-search hidden-print'
		});
		this.listenTo(this.model.table, 'sync', this.renderGrid, this);
	},
	renderGrid: function() {
		var me = this;
		this.footer = {};
		var columns = [];
		if (this.model.table.length > 1) {
			var upkeep = APPSTATE.get('upkeep');
			var s = this.model.stairs.get(APPSTATE.get('id_stair'));
			var hidden_columns = [];
			if (!s.get('errors_column')) {
				hidden_columns.push('Rotunjire');
			}
			if (!s.get('payments_column')) {
				hidden_columns.push('Plati');
			}
			if (upkeep) {
				this.$el.find('#activateUpkeep').removeClass('hidden');
				this.$el.find('#export').removeClass('hidden');
				var dactive = APPSTATE.get('date_upkeep_active');
				var d = APPSTATE.get('date_upkeep');
				if (upkeep.active || (dactive && (d < dactive || d - dactive > 86400 * 32))) {
					this.$el.find('#activateUpkeep').prop('disabled', true);
				} else {
					this.$el.find('#activateUpkeep').prop('disabled', false);
				}
			}
			$('#delete').prop('disabled', false);
			this.model.table.each(function(m) {
				_.each(m.attributes, function(i, j) {
					if (!(j in me.footer) && j != 'APARTAMENT' && hidden_columns.indexOf(j) == -1) {
						me.footer[j] = 0;
					}
					if (j in me.footer) {
						me.footer[j] += m.get(j);
						if (j == 'Total' && hidden_columns.indexOf('Plati') > -1) {
							me.footer[j] += m.get('Plati');
						}
					}
				});
			});
			_.each(this.model.table.at(0).attributes, function(v, k) {
				if (k === 'APARTAMENT') {
					columns.push({name: k, label: 'Apartament', cell: 'integer', editable: false});
				} else if (k == 'Total') {
					columns.push({name: k,
						cell: Backgrid.NumberCell.extend({
							formatter: {
								fromRaw: function (rawData, model) {
									return APPSTATE.formatWithPrecision(hidden_columns.indexOf('Plati') == -1 ? rawData : rawData + model.get('Plati'));
								}
							},
							render: function() {
								Backgrid.NumberCell.prototype.render.apply(this, arguments);
								this.$el.css('fontWeight', 'bold');
								return this;
							}
						}),
						editable: false
					});
				} else if (hidden_columns.indexOf(k) == -1 && ((k != 'Subtotal' && Math.abs(me.footer[k]) >= APPSTATE.error()) ||
					(k == 'Subtotal' && Math.abs(me.footer[k] - me.footer['Total']) >= APPSTATE.error()))) {
					columns.push({name: k, cell: 'number', editable: false});
				} else {
					delete me.footer[k];
				}
			});
		} else {
			this.$el.find('#activateUpkeep').addClass('hidden');
			this.$el.find('#export').addClass('hidden');
		}
		this.grid.columns.reset(columns);
	},
	render: function(eventName) {
		this.$el.html(this.template());
		var elem = this.$el.find("#table");
		elem.append(new ButtonsView().render().el);
		elem.find('#viewfilter').append(this.filter.render().el);
		this.$el.find('#add').addClass('hidden');
		this.$el.find('#delete').addClass('hidden');
		this.renderGrid();
		elem.append(this.grid.render().el);
		return this;
	}
});
window.ConfigurationView = AppView.extend({
	template: _.template($('#tpl-edit-configuration').html()),
	events: {
		"click #save": "savePerson"
	},
	getPerson: function() {
		return this.model.persons.get(APPSTATE.user.get('id_person'));
	},
	getStair: function() {
		return this.model.stairs.get(APPSTATE.get('id_stair'));
	},
	savePerson: function() {
		if (APPSTATE.isUserRoot()) {
			this.getStair().save({
				errors_column: $('#stairErrors').prop('checked') ? 1 : 0,
				payments_column: $('#stairPayments').prop('checked') ? 1 : 0
			}, {wait: true});
		}
		var m = this.getPerson();
		m.save({
			name: $('#personName').val(),
			telephone: $('#personTelephone').val(),
			email: $('#personEmail').val(),
			notify: $('#personNotify').prop('checked') ? 1 : 0,
			id_person_role: parseInt($('#personRole').val()),
			id_person_job: parseInt($('#personJob').val()),
			id_apartment: parseInt($('#personApartment').val())
		}, {wait: true});
		if (m.validationError) {
			this.showErrors(m, {name: 'personNameGroup', email: 'personEmailGroup', telephone: 'personTelephoneGroup'});
		}
	},
	render: function(eventName) {
		var p = this.getPerson();
		this.$el.html(this.template({
			apartments: this.model.apartments.toJSON(),
			personRoles: this.model.personRoles.toJSON(),
			personJobs: this.model.personJobs.toJSON(),
			person: p ? p.toJSON() : {notify: true},
			stair: this.getStair().toJSON()
		}));
		this.$el.append(new ButtonsView().render().el);
		//this.$el.find('#changePassword').removeClass('hidden'); //TODO: demo
		//this.$el.find('#save').removeClass('hidden'); //TODO: demo
		this.$el.find('#add').addClass('hidden');
		this.$el.find('#delete').addClass('hidden');
		return this;
	}
});
window.ContactView = AppView.extend({
	template: _.template($('#tpl-contact').html()),
	events: {
		"click #send": "sendMessage"
	},
	sendMessage: function() {
		var m = new ContactMessage({
			subject: $('#contactSubject').val(),
			message: $('#contactMessage').val()
		});
		m.save();
		if (m.validationError) {
			this.showErrors(m, {subject: 'contactSubjectGroup', message: 'contactMessageGroup'});
		} else {
			$('#contactModal').modal('hide');
		}
	},
	render: function(eventName) {
		this.$el.html(this.template());
		return this;
	}
});
window.AddPersonView = AppView.extend({
	template: _.template($('#tpl-add-person').html()),
	personTemplate: _.template($('#tpl-edit-configuration').html()),
	events: {
		"hidden.bs.modal #addPersonModal": "remove",
		"click #addPersonButton": "addPerson"
	},
	render: function(eventName) {
		this.$el.html(this.template());
		this.renderContainer();
		return this;
	},
	renderContainer: function() {
		this.$el.find('#addPersonContainer').html(this.personTemplate({
			apartments: this.model.apartments.toJSON(),
			personRoles: this.model.personRoles.toJSON(),
			personJobs: this.model.personJobs.toJSON(),
			stair: this.model.stairs.get(APPSTATE.get('id_stair')).toJSON(),
			person: {notify: true}
		}));
	},
	addPerson: function() {
		var pn = $('#personNotify');
		var m = APP.persons.create({
			name: $('#personName').val(),
			telephone: $('#personTelephone').val(),
			email: $('#personEmail').val(),
			notify: pn.prop('checked') ? 1 : 0,
			id_person_role: parseInt($('#personRole').val()),
			id_person_job: parseInt($('#personJob').val()),
			id_apartment: parseInt($('#personApartment').val())
		}, {wait: true});
		if (m.validationError) {
			this.showErrors(m, {name: 'personNameGroup', email: 'personEmailGroup', telephone: 'personTelephoneGroup'});
		} else {
			$('#addPersonModal').modal('hide');
		}
	}
});
window.AddApartmentView = AppView.extend({
	template: _.template($('#tpl-add-apartment').html()),
	events: {
		"hidden.bs.modal #addApartmentModal": "remove",
		"click #addApartmentButton": "addApartment"
	},
	render: function(eventName) {
		this.$el.html(this.template({
			persons: this.model.persons.toJSON()
		}));
		return this;
	},
	addApartment: function() {
		var m = APP.apartments.create({
			number: parseInt($('#apartmentNumber').val()),
			name: $('#apartmentName').val(),
			id_person: parseInt($('#apartmentPerson').val()),
			current: parseFloat($('#apartmentCurrent').val()),
			pending: parseFloat($('#apartmentPending').val()),
			penalty: parseFloat($('#apartmentPenalty').val()),
		}, {wait: true});
		if (m.validationError) {
			this.showErrors(m, {number: 'apartmentNumberGroup'});
		} else {
			$('#addApartmentModal').modal('hide');
		}
	}
});
window.AddUserView = AppView.extend({
	template: _.template($('#tpl-add-user').html()),
	events: {
		"hidden.bs.modal #addUserModal": "remove",
		"click #addUserButton": "addUser"
	},
	render: function(eventName) {
		this.$el.html(this.template({
			persons: this.model.persons.toJSON(),
			apartments: this.model.apartments.toJSON()
		}));
		return this;
	},
	addUser: function() {
		if ($('#userPassword').val() != $('#userPassword2').val()) {
			if (!this.hasPasswordError) {
				this.hasPasswordError = true;
				$('#userPasswordGroup2').addClass('has-error');
			}
		} else {
			var m = APP.users.create({
				username: $('#userName').val(),
				password: $('#userPassword').val(),
				id_person: parseInt($('#userPerson').val()),
				id_apartment: parseInt($('#userApartment').val())
			}, {wait: true});
			if (m.validationError) {
				this.showErrors(m, {username: 'userNameGroup', password: 'userPasswordGroup1'});
			} else {
				$('#addUserModal').modal('hide');
			}
		}
	}
});
window.AddCoefficientView = AppView.extend({
	template: _.template($('#tpl-add-coefficient').html()),
	events: {
		"hidden.bs.modal #addCoefficientModal": "remove",
		"click #addCoefficientButton": "addCoefficient"
	},
	render: function(eventName) {
		this.$el.html(this.template());
		return this;
	},
	addCoefficient: function() {
		var m = APP.coefficients.create({
			name: $('#coefficientName').val()
		}, {wait: true});
		if (m.validationError) {
			this.showErrors(m, {name: 'coefficientNameGroup'});
		} else {
			$('#addCoefficientModal').modal('hide');
		}
	}
});
window.AddExpenseView = AppView.extend({
	template: _.template($('#tpl-add-expense').html()),
	events: {
		"hidden.bs.modal #addExpenseModal": "remove",
		"click #addExpenseButton": "addExpense"
	},
	render: function(eventName) {
		this.$el.html(this.template({
			coefficients: this.model.coefficients.toJSON()
		}));
		return this;
	},
	addExpense: function() {
		var m = APP.expenses.create({
			name: $('#expenseName').val(),
			title: $('#expenseTitle').val(),
			supplier: $('#expenseSupplier').val(),
			id_coefficient: parseInt($('#expenseCoefficient').val()),
			unit: $('#expenseUnit').val(),
			value: null,
			quantity: null
		}, {wait: true});
		if (m.validationError) {
			this.showErrors(m, {name: 'expenseNameGroup'});
		} else {
			$('#addExpenseModal').modal('hide');
		}
	}
});
window.ChangePasswordView = AppView.extend({
	template: _.template($('#tpl-change-password').html()),
	events: {
		"hidden.bs.modal #changePasswordModal": "remove",
		"click #changePasswordButton": "changePassword"
	},
	render: function(eventName) {
		this.$el.html(this.template());
		return this;
	},
	changePassword: function() {
		var m = new ChangePassword({
			new_password: $('#newPassword').val(),
			new_password2: $('#newPassword2').val(),
			old_password: $('#oldPassword').val()
		});
		m.save({}, {wait: true});
		if (m.validationError) {
			this.showErrors(m, {
				new_password: 'newPasswordGroup',
				old_password: 'oldPasswordGroup'
			});
		} else {
			$('#changePasswordModal').modal('hide');
		}
	}
});
window.AddPaymentView = AppView.extend({
	template: _.template($('#tpl-add-payment').html()),
	events: {
		"hidden.bs.modal #addPaymentModal": "remove",
		"click #addPaymentButton": "addPayment",
		"change #paymentSeries": "changeSeries",
		"change #paymentApartment": "changeApartment"
	},
	render: function(eventName) {
		var d = new Date();
		var s = this.model.series.at(0);
		this.$el.html(this.template({
			apartments: this.model.apartments.toJSON(),
			total: this.getApartmentTotal(this.model.apartments.at(0).get('id')),
			series: this.model.series.toJSON(),
			defaultNumber: this.model.payments.maxNumberForSeries(s.get('id'), s.get('number')) + 1,
			defaultDate: d.getDate() + '/' + (d.getMonth() + 1) + '/' + d.getFullYear()
		}));
		this.$el.find('#paymentDatePicker').datetimepicker({
                    pickTime: false,
		    language: APPSTATE.language
		});
		return this;
	},
	addPayment: function() {
		var dateParts = $('#paymentDate').val().split('/');
		var m = APP.payments.create({
			value: parseFloat($('#paymentValue').val()),
			number: parseInt($('#paymentNumber').val()),
			date: new Date(dateParts[2], dateParts[1] - 1, dateParts[0]).getTime() / 1000,
			id_apartment: parseInt($('#paymentApartment').val()),
			id_invoice_series: parseInt($('#paymentSeries').val())
		}, {wait: true});
		if (m.validationError) {
			this.showErrors(m, {value: 'paymentValueGroup', number: 'paymentNumberGroup', date: 'paymentDateGroup'});
		} else {
			$('#addPaymentModal').modal('hide');
		}
	},
	changeSeries: function(e) {
		var id = $('#paymentSeries').val();
		var s = this.model.series.get(id).get('number');
		$('#paymentNumber').val(this.model.payments.maxNumberForSeries(id, s) + 1);
	},
	getApartmentTotal: function(apt) {
		var t = this.model.table.get(this.model.apartments.get(apt).get('number')).get('Total');
		return (t > 0 ? t : 0).toFixed(APPSTATE.precision);
	},
	changeApartment: function(e) {
		$('#paymentValue').val(this.getApartmentTotal($('#paymentApartment').val()));
	}
});
window.AddSeriesView = AppView.extend({
	template: _.template($('#tpl-add-series').html()),
	events: {
		"hidden.bs.modal #addSeriesModal": "remove",
		"click #addSeriesButton": "addSeries"
	},
	render: function(eventName) {
		this.$el.html(this.template());
		return this;
	},
	addSeries: function() {
		var m = APP.series.create({
			name: $('#seriesName').val()
		}, {wait: true});
		if (m.validationError) {
			this.showErrors(m, {name: 'seriesNameGroup'});
		} else {
			$('#addSeriesModal').modal('hide');
		}
	}
});
window.AddMessagesView = AppView.extend({
	template: _.template($('#tpl-add-messages').html()),
	events: {
		"hidden.bs.modal #addMessagesModal": "remove",
		"click #addMessagesButton": "addMessages"
	},
	render: function(eventName) {
		var d = new Date();
		this.$el.html(this.template({
			defaultDate: d.getDate() + '/' + (d.getMonth() + 1) + '/' + d.getFullYear()
		}));
		this.$el.find('#messagesDatePicker').datetimepicker({
                    pickTime: false,
		    language: APPSTATE.language
		});
		return this;
	},
	addMessages: function() {
		var dateParts = $('#messagesDate').val().split('/');
		var m = APP.messages.create({
			message: $('#messagesMsg').val(),
			created_date: (new Date()).getTime() / 1000,
			expire_date: new Date(dateParts[2], dateParts[1] - 1, dateParts[0]).getTime() / 1000
		}, {wait: true});
		if (m.validationError) {
			this.showErrors(m, {message: 'messagesMsgGroup', expire_date: 'messagesDateGroup'});
		} else {
			$('#addMessagesModal').modal('hide');
		}
	}
});
window.UpdateIndexesView = AppView.extend({
	template: _.template($('#tpl-update-indexes').html()),
	events: {
		"change :input": "updateTotal"
	},
	initialize: function() {
		this.listenTo(this.model.expenses, 'remove sync', this.render, this);
		this.listenTo(this.model.indexes, 'remove sync', this.render, this);
	},
	updateTotal: function(event) {
		var me = this;
		var cv = this.model.coefficient_mod_values.get(APPSTATE.get('id_apartment'));
		this.model.expenses.each(function(e) {
			if (e.get('id_mod_type') == 3 && event.currentTarget.id.indexOf(e.get('id') + 'Index') == 0) {
				var index = me.model.indexes.find(function(m) {
					return m.get('id_expense') == e.get('id');
				});
				var root = e.get('id');
				var v1 = parseFloat($('#' + root + 'Index1').val());
				var t1 = v1 - (index ? index.get('index1_old') : 0);
				var v2 = parseFloat($('#' + root + 'Index2').val());
				var t2 = v2 - (index ? index.get('index2_old') : 0);
				var v3 = parseFloat($('#' + root + 'Index3').val());
				var t3 = v3 - (index ? index.get('index3_old') : 0);
				var t = t1 + t2 + t3 - (index ? index.get('estimated') : 0);
				$('#' + root + 'Total1').text(t1.toFixed(APPSTATE.precision));
				$('#' + root + 'Total2').text(t2.toFixed(APPSTATE.precision));
				$('#' + root + 'Total3').text(t3.toFixed(APPSTATE.precision));
				$('#' + root + 'Total').text(t.toFixed(APPSTATE.precision));
				cv.set(e.get('name') + '_VAL', t);
				if (index) {
					index.set('index1', v1);
					index.set('index2', v2);
					index.set('index3', v3);
					index.save();
				} else {
					index = me.model.indexes.create({id_apartment: APPSTATE.get('id_apartment'),
						id_expense: root, index1: v1, index2: v2, index3: v3,
						index1_old: 0, index2_old: 0, index3_old: 0, estimated: 0});
				}
				if (index.validationError) {
					me.showErrors(index, {index1: root + 'Index1Group',
					index2: root + 'Index2Group',
					index3: root + 'Index3Group'});
				}
			}
		});
		cv.save();
	},
	render: function(eventName) {
		this.$el.html(this.template({
			expenses: this.model.expenses.toJSON(),
			indexes: this.model.indexes.toJSON()
		}));
		return this;
	}
});
window.ImportCoefficientsView = AppView.extend({
	template: _.template($('#tpl-import-coefficients').html()),
	events: {
		"hidden.bs.modal #importCoefficientsModal": "remove"
	},
	render: function(eventName) {
		this.$el.html(this.template());
		var me = this;
		this.$el.find('#importCoefficientsForm').ajaxForm({
			success: function() {
				$('#importCoefficientsModal').modal('hide');
				me.model.fetch();
			},
			beforeSubmit: function() {
				if (!$('#importCoefficientsFile').val() || !$('#importCoefficientsFile').val().match(/.csv$/)) {
					if (!me.hasFileError) {
						me.hasFileError = true;
						$('#importCoefficientsGroup').addClass('has-error');
					}
					return false;
				}
			}
		});
		return this;
	}
});
window.ImportApartmentsView = AppView.extend({
	template: _.template($('#tpl-import-apartments').html()),
	events: {
		"hidden.bs.modal #importApartmentsModal": "remove"
	},
	render: function(eventName) {
		this.$el.html(this.template());
		var me = this;
		this.$el.find('#importApartmentsForm').ajaxForm({
			success: function() {
				$('#importApartmentsModal').modal('hide');
				me.model.fetch();
			},
			beforeSubmit: function() {
				if (!$('#importApartmentsFile').val() || !$('#importApartmentsFile').val().match(/.csv$/)) {
					if (!me.hasFileError) {
						me.hasFileError = true;
						$('#importApartmentsGroup').addClass('has-error');
					}
					return false;
				}
			}
		});
		return this;
	}
});
window.ImportModCoefficientsView = AppView.extend({
	template: _.template($('#tpl-import-mod-coefficients').html()),
	events: {
		"hidden.bs.modal #importModCoefficientsModal": "remove"
	},
	render: function(eventName) {
		this.$el.html(this.template());
		var me = this;
		this.$el.find('#importModCoefficientsForm').ajaxForm({
			success: function() {
				$('#importModCoefficientsModal').modal('hide');
				me.model.coefficient_mod_values.fetch();
				me.model.indexes.fetch();
			},
			beforeSubmit: function() {
				if (!$('#importModCoefficientsFile').val() || !$('#importModCoefficientsFile').val().match(/.csv$/)) {
					if (!me.hasFileError) {
						me.hasFileError = true;
						$('#importModCoefficientsGroup').addClass('has-error');
					}
					return false;
				}
			}
		});
		return this;
	}
});
window.ChartExpensesView = AppView.extend({
	template: _.template($('#tpl-chart-expenses').html()),
	legend: _.template($('#tpl-legend').html()),
	options: _.template($('#tpl-chart-period').html()),
	events: {
		"click input[type=checkbox]": "updateChart",
		"change input[type=radio]": "changeChart",
		"change #chartApartment": "changeChart",
		"change #chartslider": "changeChart"
	},
	checked: [],
	changeChart: function(event) {
		var e = $(event.currentTarget);
		if (e.attr('name') == 'charttype') {
			e = $('#chartApartment');
			e.prop('disabled', !e.attr('disabled'));
		}
		this.model.chartExpenses.fetch({data: {
			type: $('#typeForm input[name=charttype]:checked').val(),
			value: $('#typeForm input[name=chartvalue]:checked').val(),
			apartment: $('#chartApartment').val(),
			period: $('#chartoptions').val()
		}});
	},
	updateChart: function(event) {
		this.checked = [];
		var data = { labels: this.model.chartExpenses.get('labels'), datasets: [] };
		var c = this.checked;
		_.each(this.model.chartExpenses.get('datasets'), function(d) {
			$('#legendForm input[type=checkbox]').each(function() {
				if ($(this).attr('checked') == 'checked' && $(this).attr('name') == d.title) {
					data.datasets.push(d);
					c.push(d.title);
				}
			});
		});
		this.renderChart(data);
	},
	initialize: function() {
		this.listenTo(this.model.chartExpenses, 'sync', this.renderChartAndLabels, this);
	},
	render: function(eventName) {
		this.$el.html(this.template({
			apartments: this.model.apartments.toJSON()
		}));
		this.renderChartAndLabels();
		d1 = new Date(APPSTATE.get('date_upkeep') * 1000);
		d2 = new Date(APPSTATE.get('oldest_date_upkeep') * 1000);
		m = Math.min((d1.getYear() - d2.getYear())* 12 + d1.getMonth() - d2.getMonth() + 1, 36);
		this.$el.find('#chartslider').html(this.options({
			period: m
		}));
		return this;
	},
	renderChartAndLabels: function() {
		if (this.model.chartExpenses.has('labels')) {
			this.$el.find('#legendExpenses').html(this.legend({
				chart: this.model.chartExpenses.toJSON(),
				checked: this.checked
			}));
			this.updateChart();
		}
	},
	renderChart: function(data) {
		new Chart(this.$el.find('#chartExpenses').get(0).getContext("2d")).Line(data);
	}
});
var AppRouter = Backbone.Router.extend({
	routes: {
		'': 'viewTable',
		'edit/apartments': 'defineApartments',
		'edit/persons': 'definePersons',
		'edit/users': 'defineUsers',
		'edit/expenses': 'editExpenses',
		'edit/quotas': 'editCoefficientValues',
		'edit/mod_quotas': 'editCoefficientModValues',
		'edit/payments': 'editPayments',
		'edit/series': 'editSeries',
		'edit/configuration': 'editConfiguration',
		'edit/messages': 'editMessages',
		'chart/expenses': 'chartExpenses',
		'edit/contact': 'editContact',
		'edit/indexes': 'editIndexes',
		'view/tables': 'viewTables'
	},
	initialize: function(options) {
		this.stairs = options.stairs;
		this.apartments = options.apartments;
		this.messages = options.messages;
		this.coefficients = options.coefficients;
		this.expenses = options.expenses;
		this.users = options.users;
		this.persons = options.persons;
		this.personRoles = options.personRoles;
		this.personJobs = options.personJobs;
		this.modTypes = options.modTypes;
		this.coefficient_values = options.coefficientValues;
		this.coefficient_mod_values = options.coefficientModValues;
		this.table = options.table;
		this.configuration = options.configuration;
		this.series = options.series;
		this.payments = options.payments;
		this.indexes = options.indexes;
		this.chartExpenses = new ChartExpensesDataset();
		var me = this;
		APPSTATE.on({'change': function() {
			me.persons.fetch({reset: true, silent: true});
			me.users.fetch({reset: true, silent: true});
			me.messages.fetch({reset: true, silent: true});
			me.configuration.fetch({reset: true, silent: true});
			me.series.fetch({reset: true, silent: true});
			me.expenses.fetch({reset: true, silent: true});
			me.payments.fetch({reset: true, silent: true});
			me.apartments.fetch({reset: true, silent: true});
			me.indexes.fetch({reset: true, silent: true});
			me.coefficient_values.fetch({reset: true, silent: true});
			me.coefficient_mod_values.fetch({reset: true, silent: true});
			me.chartExpenses.fetch({reset: true, silent: true});
			me.table.fetch({reset: true, silent: true});
		}});
		this.startEvents();
		$('#main').html(new MainView({model: options}).render().el);
		this.showLastMessage();
		this.showMainSummary();
	},
	startEvents: function() {
		var me = this;
		me.apartments.on('remove sync', function(event, model, options) {
			if (!options.silent) {
				me.table.fetch({reset: true});
			}
		}, this);
		me.payments.on('remove sync', function(event, model, options) {
			if (!options.silent) {
				me.table.fetch({reset: true});
			}
		}, this);
		me.expenses.on('remove sync', function(event, model, options) {
			if (!options.silent) {
				me.coefficient_values.fetch({reset: true, silent: true});
				me.coefficient_mod_values.fetch({reset: true});
			}
		}, this);
		me.series.on('remove sync', function(event, model, options) {
			if (!options.silent) {
				me.payments.fetch({reset: true});
			}
		}, this);
		me.coefficient_values.on('sync', function(event, model, options) {
			if (!options.silent) {
				me.table.fetch({reset: true});
			}
		}, this);
		me.coefficient_mod_values.on('sync', function(event, model, options) {
			if (!options.silent) {
				me.table.fetch({reset: true});
			}
		}, this);
	},
	showLastMessage: function() {
		var messageView = new LastMessageView({model: this.messages});
		$("#message_content").html(messageView.render().el);
	},
	showMainSummary: function() {
		var summaryView = new MainSummaryView({
			model: {
			table: this.table,
			apartments: this.apartments,
			payments: this.payments
			}});
		$("#summary_content").html(summaryView.render().el);
	},
	switchView: function(view, model) {
		this.lastView && this.lastView.remove();
		this.lastView = new view({model: model});
		$('#content').html(this.lastView.render().el);
	},
	viewTable: function() {
		this.switchView(TableView, {
			table: this.table,
			stairs: this.stairs
		});
	},
	defineApartments: function() {
		this.switchView(DefineApartmentsView, {
			apartments: this.apartments,
			persons: this.persons,
			payments: this.payments,
			table: this.table
		});
	},
	definePersons: function() {
		this.switchView(DefinePersonsView, {
			persons: this.persons,
			personRoles: this.personRoles,
			personJobs: this.personJobs,
			apartments: this.apartments,
			stairs: this.stairs
		});
	},
	defineUsers: function() {
		this.switchView(DefineUsersView, {
			users: this.users,
			persons: this.persons,
			apartments: this.apartments
		});
	},
	editExpenses: function() {
		this.switchView(ExpensesView, {
			expenses: this.expenses,
			coefficients: this.coefficients,
			configuration: this.configuration
		});
	},
	editCoefficientValues: function() {
		this.switchView(EditCoefficientsView, {
			coefficient_values: this.coefficient_values,
			apartments: this.apartments
		});
	},
	editCoefficientModValues: function() {
		this.switchView(EditCoefficientModsView, {
			coefficient_mod_values: this.coefficient_mod_values,
			modTypes: this.modTypes,
			indexes: this.indexes,
			apartments: this.apartments
		});
	},
	editConfiguration: function() {
		this.switchView(ConfigurationView, {
			apartments: this.apartments,
			personRoles: this.personRoles,
			personJobs: this.personJobs,
			persons: this.persons,
			stairs: this.stairs
		});
	},
	editPayments: function() {
		this.switchView(PaymentsView, {
			payments: this.payments,
			series: this.series,
			apartments: this.apartments,
			table: this.table
		});
	},
	editSeries: function() {
		this.switchView(InvoiceSeriesView, {
			series: this.series,
			payments: this.payments
		});
	},
	chartExpenses: function() {
		this.chartExpenses.fetch({silent: true});
		this.switchView(ChartExpensesView, {
			chartExpenses: this.chartExpenses,
			apartments: this.apartments
		});
	},
	editMessages: function() {
		this.switchView(MessagesView, this.messages);
	},
	editIndexes: function() {
		this.switchView(UpdateIndexesView, {
			expenses: this.expenses,
			indexes: this.indexes,
			coefficient_mod_values: this.coefficient_mod_values
		});
	},
	viewTables: function() {
		this.switchView(TablesView, {
			expenses: this.expenses,
			coefficient_values: this.coefficient_values,
			apartments: this.apartments,
			coefficients: this.coefficients
		});
	}
});

