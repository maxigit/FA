/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/
function focus_alloc(i) {
    save_focus(i);
	i.setAttribute('_last', get_amount(i.name));
}

function blur_alloc(i) {
		var change = get_amount(i.name);
		
		if (i.name != 'amount' && i.name != 'charge' && i.name != 'discount')
			change = Math.min(change, get_amount('maxval'+i.name.substr(6), 1))

		price_format(i.name, change, user.pdec);
		if (i.name != 'amount' && i.name != 'charge') {
			if (change<0) change = 0;
			change = change-i.getAttribute('_last');
			if (i.name == 'discount') change = -change;

			var total = get_amount('amount')+change;
			price_format('amount', total, user.pdec, 0);
		}
}

function allocate_all(doc) {
    return allocate_ppd(doc, 0, 0)
}

var allocated_ppd = {};
function allocate_ppd(doc, ppd, ppd_gst) {
  if(allocated_ppd[doc]) allocate_none(doc);
	var amount = get_amount('amount'+doc);
	var unallocated = get_amount('un_allocated'+doc);
	var total = get_amount('amount');
	var left = 0;
	total -=  (amount-unallocated);
	left -= (amount-unallocated);
	amount = unallocated;
	if(left<0) {
		total  += left;
		amount += left;
		left = 0;
	}
	  price_format('amount'+doc, amount, user.pdec);
	  price_format('amount', total, user.pdec);
    // we need to store the ppds to be able to clear the discount field if needed
    // when unallocated
    if(!allocated_ppd[doc]) {
        allocated_ppd[doc] = {amount:ppd, gst:ppd_gst};
        var discount = get_amount('discount');
        var vat_discount = get_amount('vat_discount');
	      price_format('discount', discount+ppd, user.pdec);
	      price_format('vat_discount', vat_discount+ppd_gst, user.pdec);
        
    }
    update_real_amount();
}

function allocate_none(doc) {
	var amount = get_amount('amount'+doc);
	var total = get_amount('amount');
	price_format('amount'+doc, 0, user.pdec);
	price_format('amount', total-amount, user.pdec);

  var discount = get_amount('discount');
  var vat_discount = get_amount('vat_discount');
  var ppds = allocated_ppd[doc];
    price_format('discount', discount-ppds.amount, user.pdec);
    price_format('vat_discount', vat_discount-ppds.vat, user.pdec);

    allocated_ppd[doc] = null;

    update_real_amount();
}

function update_real_amount() {
    var amount = get_amount('amount');
    var discount = get_amount('discount');
    price_format('real_amount', amount-discount, user.pdec);
}

var allocations = {
	'.amount': function(e) {
 		if(e.name == 'allocated_amount' || e.name == 'bank_amount')
 		{
  		  e.onblur = function() {
			var dec = this.getAttribute("dec");
			price_format(this.name, get_amount(this.name), dec);
		  };
 		} else {
			e.onblur = function() {
				blur_alloc(this);
			};
			e.onfocus = function() {
				focus_alloc(this);
			};
		}
	}
}

Behaviour.register(allocations);
