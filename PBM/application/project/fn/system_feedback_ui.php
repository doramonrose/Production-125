<?php
	$pm_fb_page = isset($page) ? (string)$page : '';
	$pm_fb_role = (defined('PLAN_ADMIN') && PLAN_ADMIN) ? 'plan' : 'user';
	$pm_fb_ajax = (defined('PLAN_ADMIN') && PLAN_ADMIN)
		? 'save_system_feedback_ajax.php'
		: 'save_system_feedback_ajax.php';
?>
<div class="modal fade" id="modalSystemFeedback" tabindex="-1" role="dialog" aria-labelledby="pmFbTitle" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document" style="max-width:520px;">
		<div class="modal-content pm-fb-modal">
			<button type="button" class="close pm-fb-close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<div class="pm-fb-head">
				<h5 class="pm-fb-title" id="pmFbTitle">ความพึงพอใจต่อการใช้งานระบบบริหารโครงการ</h5>
				<p class="pm-fb-sub">ความคิดเห็นของท่านมีส่วนช่วยให้เราพัฒนาระบบให้ตอบโจทย์การใช้งานมากยิ่งขึ้น</p>
			</div>
			<div class="pm-fb-body">
				<div class="pm-fb-label">ความพึงพอใจโดยรวม</div>
				<div class="pm-fb-stars" id="pmFbStars">
					<button type="button" class="pm-fb-star" data-score="1" aria-label="1"><i class="fa fa-star"></i></button>
					<button type="button" class="pm-fb-star" data-score="2" aria-label="2"><i class="fa fa-star"></i></button>
					<button type="button" class="pm-fb-star" data-score="3" aria-label="3"><i class="fa fa-star"></i></button>
					<button type="button" class="pm-fb-star" data-score="4" aria-label="4"><i class="fa fa-star"></i></button>
					<button type="button" class="pm-fb-star" data-score="5" aria-label="5"><i class="fa fa-star"></i></button>
				</div>
				<div class="pm-fb-scale"><span>น้อยที่สุด</span><span>มากที่สุด</span></div>
				<div class="pm-fb-hint" id="pmFbHint">โปรดเลือกคะแนนความพึงพอใจของท่าน</div>
				<input type="hidden" id="pmFbRating" value="0">

				<hr class="pm-fb-hr">
				<div class="pm-fb-label">ข้อเสนอแนะเพิ่มเติม</div>
				<div class="pm-fb-note">โปรดระบุข้อเสนอแนะ ปัญหาที่พบ หรือฟังก์ชันที่อยากให้เพิ่มเติม</div>
				<textarea id="pmFbComment" class="form-control pm-fb-comment" rows="4" placeholder="พิมพ์ข้อเสนอแนะของท่านที่นี่ (ไม่บังคับ)"></textarea>
			</div>
			<div class="pm-fb-foot">
				<button type="button" class="btn pm-fb-cancel" data-dismiss="modal">ยกเลิก</button>
				<button type="button" class="btn pm-fb-submit" id="pmFbSubmit">ส่งข้อเสนอแนะ</button>
			</div>
		</div>
	</div>
</div>
<script>
(function($){
	if (!$ || !$.fn) return;
	var ajaxUrl = <?= json_encode($pm_fb_ajax, JSON_UNESCAPED_UNICODE) ?>;
	var pageName = <?= json_encode($pm_fb_page, JSON_UNESCAPED_UNICODE) ?>;
	var roleName = <?= json_encode($pm_fb_role, JSON_UNESCAPED_UNICODE) ?>;
	var $modal = $('#modalSystemFeedback');
	var $stars = $('#pmFbStars .pm-fb-star');
	var $rating = $('#pmFbRating');
	var $hint = $('#pmFbHint');
	var justSaved = false;
	function paint(n){
		$stars.each(function(){
			var s = parseInt($(this).attr('data-score'), 10) || 0;
			$(this).toggleClass('is-on', s <= n);
		});
	}
	function resetBtn(){
		$('#pmFbSubmit').prop('disabled', false).text('ส่งข้อเสนอแนะ');
	}
	function closeModal(){
		justSaved = true;
		$('[data-target="#modalSystemFeedback"]').prop('disabled', true);
		$modal.removeClass('show').hide().attr('aria-hidden', 'true');
		try { $modal.modal('hide'); } catch (e) {}
		$('.modal-backdrop').remove();
		$('body').removeClass('modal-open').css('padding-right', '');
		setTimeout(function(){
			$('[data-target="#modalSystemFeedback"]').prop('disabled', false);
			justSaved = false;
		}, 500);
	}
	function thankThenClose(msg){
		justSaved = true;
		$('[data-target="#modalSystemFeedback"]').prop('disabled', true);
		var finish = function(){
			closeModal();
			resetBtn();
		};
		if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
			Swal.fire({
				icon: 'success',
				title: 'ขอบคุณค่ะ',
				text: msg || 'บันทึกข้อเสนอแนะเรียบร้อยแล้ว',
				confirmButtonText: 'ตกลง',
				confirmButtonColor: '#15803d',
				allowOutsideClick: false,
				allowEscapeKey: false
			}).then(function(){
				finish();
			});
			return;
		}
		finish();
	}
	function parseRes(raw){
		if (raw && typeof raw === 'object') return raw;
		var text = String(raw || '');
		try { return JSON.parse(text); } catch (e) {}
		var m = text.match(/\{[\s\S]*\}/);
		if (m) { try { return JSON.parse(m[0]); } catch (e2) {} }
		return {};
	}
	$stars.on('click', function(){
		var n = parseInt($(this).attr('data-score'), 10) || 0;
		$rating.val(n);
		paint(n);
		$hint.text(n > 0 ? 'คะแนนที่เลือก : ' + n + ' จาก 5' : 'โปรดเลือกคะแนนความพึงพอใจของท่าน');
	});
	$modal.on('show.bs.modal', function(e){
		if (justSaved) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
	});
	$modal.on('hidden.bs.modal', function(){
		$rating.val(0);
		paint(0);
		$('#pmFbComment').val('');
		$hint.text('โปรดเลือกคะแนนความพึงพอใจของท่าน');
		resetBtn();
	});
	$('#pmFbSubmit').on('click', function(e){
		e.preventDefault();
		e.stopPropagation();
		var n = parseInt($rating.val(), 10) || 0;
		if (n < 1 || n > 5) {
			$hint.text('กรุณาเลือกคะแนนความพึงพอใจ');
			return;
		}
		var $btn = $(this);
		$btn.prop('disabled', true).text('กำลังบันทึก...');
		$.ajax({
			url: ajaxUrl,
			type: 'POST',
			dataType: 'text',
			timeout: 20000,
			data: {
				role: roleName,
				page: pageName,
				rating: n,
				comment: $('#pmFbComment').val() || ''
			}
		}).done(function(raw){
			var res = parseRes(raw);
			if (res.ok == 1 || res.ok === true || String(res.ok) === '1') {
				thankThenClose(res.msg || 'บันทึกข้อเสนอแนะเรียบร้อยแล้ว');
				return;
			}
			resetBtn();
			$hint.text(res.msg || 'บันทึกไม่สำเร็จ');
		}).fail(function(){
			resetBtn();
			$hint.text('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
		});
	});
})(window.jQuery);
</script>
